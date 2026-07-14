<?php

namespace App\Http\Controllers;

use App\Models\Comissao;
use App\Models\Comprador;
use App\Models\DocumentoProcesso;
use App\Models\Fatura;
use App\Models\HistoricoProcesso;
use App\Models\Negociacao;
use App\Models\Processo;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ProcessoController extends Controller
{
    public function index()
    {
        return view('content.processos.index', [
            'statuses' => Processo::STATUSES,
            'servicos' => Servico::orderBy('nome')->get(['id', 'nome']),
            'isAdmin' => auth()->user()->hasRole('admin'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isComprador = $user->hasRole('comprador');

        $query = Processo::query()
            ->with('servico:id,nome')
            ->withCount('documentos');

        if ($isAdmin) {
            $query->with('user:id,name');
        } elseif ($isComprador) {
            // Comprador vê apenas processos aos quais está vinculado
            $query->whereHas('comprador', fn ($q) => $q->where('user_id', $user->id))
                  ->with('user:id,name');
        } else {
            $query->where('user_id', $user->id);
        }

        if ($s = $request->query('status')) $query->where('status', $s);
        if ($sv = $request->query('servico_id')) $query->where('servico_id', $sv);
        if ($isAdmin && ($c = trim((string) $request->query('cliente', '')))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $c . '%'));
        }
        if ($de = $request->query('cadastrado_de')) {
            $query->whereDate('created_at', '>=', $de);
        }
        if ($ate = $request->query('cadastrado_ate')) {
            $query->whereDate('created_at', '<=', $ate);
        }

        return DataTables::eloquent($query->orderByDesc('created_at'))
            ->addColumn('cliente', fn (Processo $p) => $p->user?->name ?? '—')
            ->addColumn('servico_nome', fn (Processo $p) => $p->servico?->nome ?? '—')
            ->addColumn('documento_formatado', fn (Processo $p) => strtoupper($p->tipo_documento) . ': ' . $p->documento)
            ->addColumn('status_badge', fn (Processo $p) =>
                '<span class="badge bg-label-' . $p->statusColor() . '">' . e($p->statusLabel()) . '</span>')
            ->addColumn('criado_em', fn (Processo $p) => $p->created_at?->format('d/m/Y H:i'))
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create(Request $request)
    {
        $isAdmin = $request->user()->hasRole('admin');
        $servicos = Servico::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        abort_if($servicos->isEmpty(), 422, 'Nenhum serviço ativo disponível. Peça ao administrador para cadastrar.');

        return view('content.processos.form', [
            'processo' => new Processo([
                'servico_id' => $servicos->first()->id,
                'tipo_documento' => 'cpf',
            ]),
            'dividas' => collect(),
            'veiculo' => null,
            'servicos' => $servicos,
            'isAdmin' => $isAdmin,
            'clientes' => $isAdmin ? $this->clientesParaSelect() : collect(),
            'compradores' => $isAdmin ? Comprador::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'documento', 'tipo_documento']) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isAdmin = $request->user()->hasRole('admin');
        $data = $this->validateProcesso($request, $isAdmin);

        $processo = DB::transaction(function () use ($data, $request, $isAdmin) {
            $ownerId = $isAdmin ? (int) $data['processo']['user_id'] : $request->user()->id;
            unset($data['processo']['user_id']);

            $processo = Processo::create(array_merge($data['processo'], [
                'user_id' => $ownerId,
                'status' => 'cadastrado',
            ]));

            foreach ($data['dividas'] as $divida) {
                $processo->dividas()->create($divida);
            }

            if ($data['veiculo']) {
                $processo->veiculo()->create($data['veiculo']);
            }

            // Se o serviço tem valor padrão, cria automaticamente uma comissão "a receber"
            // apontada ao próprio cliente do processo (representa o valor cobrado pelo serviço).
            $servico = Servico::find($processo->servico_id);
            if ($servico && $servico->valor_padrao && (float) $servico->valor_padrao > 0) {
                Comissao::create([
                    'licensed_by_user_id' => $ownerId,
                    'processo_id' => $processo->id,
                    'descricao' => 'Serviço ' . $servico->nome,
                    'valor' => $servico->valor_padrao,
                    'tipo' => 'a_receber',
                    'data_referencia' => now()->toDateString(),
                    'status' => 'pendente',
                ]);
            }

            HistoricoProcesso::create([
                'processo_id' => $processo->id,
                'user_id' => $request->user()->id,
                'status_anterior' => null,
                'status_novo' => 'cadastrado',
                'observacao' => $isAdmin
                    ? 'Processo cadastrado pelo administrador.'
                    : 'Processo cadastrado pelo cliente.',
            ]);

            return $processo;
        });

        return redirect()
            ->route('processos.show', $processo)
            ->with('status', 'Processo cadastrado com sucesso.');
    }

    public function show(Request $request, Processo $processo)
    {
        $this->authorizeAccess($request, $processo);

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isComprador = $user->hasRole('comprador');

        $relations = [
            'user.roles:id,name',
            'servico:id,nome',
            'dividas',
            'documentos.uploadedBy:id,name',
            'historico.user:id,name',
            'faturas',
            'veiculo',
            'comprador',
        ];
        if ($isAdmin) {
            $relations[] = 'comissoes.licenciado:id,name';
            $relations[] = 'negociacoes.inseridaPor:id,name';
        }
        $processo->load($relations);

        // Usuários para select2 no form inline de comissão — inclui role pra colorir
        $usuariosParaComissao = collect();
        if ($isAdmin) {
            $usuariosParaComissao = User::with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => optional($u->roles->first())->name ?? 'sem_role',
                ]);
        }

        return view('content.processos.show', [
            'processo' => $processo,
            'statuses' => Processo::STATUSES,
            'isAdmin' => $isAdmin,
            'isComprador' => $isComprador,
            'isOwner' => $processo->user_id === $user->id,
            'usuariosParaComissao' => $usuariosParaComissao,
        ]);
    }

    public function edit(Request $request, Processo $processo)
    {
        $isAdmin = $request->user()->hasRole('admin');
        if (! $isAdmin) {
            $this->authorizeOwner($request, $processo);
            abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser editado.');
        }

        $processo->loadMissing('veiculo');

        return view('content.processos.form', [
            'processo' => $processo,
            'dividas' => $processo->dividas,
            'veiculo' => $processo->veiculo,
            'servicos' => Servico::where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'isAdmin' => $isAdmin,
            'clientes' => $isAdmin ? $this->clientesParaSelect() : collect(),
            'compradores' => $isAdmin ? Comprador::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'documento', 'tipo_documento']) : collect(),
        ]);
    }

    public function update(Request $request, Processo $processo): RedirectResponse
    {
        $isAdmin = $request->user()->hasRole('admin');
        if (! $isAdmin) {
            $this->authorizeOwner($request, $processo);
            abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser editado.');
        }

        $data = $this->validateProcesso($request, $isAdmin);

        DB::transaction(function () use ($processo, $data, $isAdmin) {
            if (! $isAdmin) {
                unset($data['processo']['user_id']);
            }
            $processo->update($data['processo']);
            $processo->dividas()->delete();
            foreach ($data['dividas'] as $divida) {
                $processo->dividas()->create($divida);
            }

            // Veículo: upsert 1:1. Se veio dados, atualiza (ou cria); se não veio, remove existente.
            if ($data['veiculo']) {
                $processo->veiculo()->updateOrCreate(
                    ['processo_id' => $processo->id],
                    $data['veiculo']
                );
            } else {
                $processo->veiculo()->delete();
            }
        });

        return redirect()
            ->route('processos.show', $processo)
            ->with('status', 'Processo atualizado.');
    }

    public function destroy(Request $request, Processo $processo): RedirectResponse
    {
        $isAdmin = $request->user()->hasRole('admin');
        if (! $isAdmin) {
            $this->authorizeOwner($request, $processo);
            abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser excluído.');
        }

        foreach ($processo->documentos as $doc) {
            Storage::disk('local')->delete($doc->arquivo);
        }
        $processo->delete();

        return redirect()
            ->route('processos.index')
            ->with('status', 'Processo excluído.');
    }

    public function uploadDocumento(Request $request, Processo $processo): RedirectResponse
    {
        $this->authorizeAccess($request, $processo);

        $request->validate([
            'arquivo' => ['required', 'file', 'max:20480'],
            'categoria' => ['nullable', 'string', 'max:80'],
        ]);

        $file = $request->file('arquivo');
        $path = $file->store('processos/' . $processo->id, 'local');

        $processo->documentos()->create([
            'uploaded_by_user_id' => $request->user()->id,
            'categoria' => $request->input('categoria') ?: null,
            'nome_original' => $file->getClientOriginalName(),
            'arquivo' => $path,
            'tamanho_bytes' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        return back()->with('status', 'Documento enviado.');
    }

    public function destroyDocumento(Request $request, DocumentoProcesso $documento): RedirectResponse
    {
        $this->authorizeAccess($request, $documento->processo);

        $isAdmin = $request->user()->hasRole('admin');
        $isUploader = $documento->uploaded_by_user_id === $request->user()->id;
        abort_unless($isAdmin || $isUploader, 403, 'Você só pode excluir documentos que enviou.');

        Storage::disk('local')->delete($documento->arquivo);
        $documento->delete();

        return back()->with('status', 'Documento excluído.');
    }

    public function downloadDocumento(Request $request, DocumentoProcesso $documento): StreamedResponse
    {
        $this->authorizeAccess($request, $documento->processo);

        return Storage::disk('local')->download($documento->arquivo, $documento->nome_original);
    }

    public function updateStatus(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Processo::STATUSES))],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'data_protocolo_liminar' => ['nullable', 'date'],
        ]);

        $anterior = $processo->status;
        $updates = ['status' => $data['status']];

        if ($data['status'] === 'liminar_protocolada') {
            $dataProto = $data['data_protocolo_liminar'] ?? now()->toDateString();
            $updates['data_protocolo_liminar'] = $dataProto;
            $updates['data_previsao_conclusao'] = Carbon::parse($dataProto)->addDays(45)->toDateString();
        }

        if ($data['status'] === 'concluido') {
            $updates['data_conclusao'] = now()->toDateString();
        }

        $processo->update($updates);

        HistoricoProcesso::create([
            'processo_id' => $processo->id,
            'user_id' => $request->user()->id,
            'status_anterior' => $anterior,
            'status_novo' => $data['status'],
            'observacao' => $data['observacao'] ?? null,
        ]);

        return back()->with('status', 'Status atualizado.');
    }

    public function updateObservacoes(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'observacoes_admin' => ['nullable', 'string', 'max:5000'],
        ]);

        $processo->update($data);

        return back()->with('status', 'Observações salvas.');
    }

    public function storeFatura(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'vencimento' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);

        $processo->loadMissing('user:id,name,email,cpf_cnpj');

        Fatura::create([
            'processo_id' => $processo->id,
            'user_id' => $processo->user_id,
            'descricao' => $data['descricao'] ?? null,
            'valor' => $data['valor'],
            'vencimento' => $data['vencimento'],
            'status' => $data['status'],
            'pago_em' => $data['status'] === 'paga' ? now() : null,
            'metodo' => $data['status'] === 'paga' ? 'manual' : null,
            'payer_name' => $processo->user?->name,
            'payer_email' => $processo->user?->email,
            'payer_document' => $processo->user?->cpf_cnpj,
        ]);

        return back()->with('status', 'Fatura criada.');
    }

    public function destroyFatura(Request $request, Fatura $fatura): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($fatura->processo_id, 404);

        $processo = $fatura->processo;
        $fatura->delete();

        return redirect()
            ->route('processos.show', $processo)
            ->with('status', 'Fatura excluída.');
    }

    public function storeComissao(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'licensed_by_user_id' => ['required', 'exists:users,id'],
            'descricao' => ['required', 'string', 'max:160'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'tipo' => ['required', 'in:a_receber,a_pagar'],
            'data_referencia' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);

        Comissao::create(array_merge($data, [
            'processo_id' => $processo->id,
            'pago_em' => $data['status'] === 'paga' ? now() : null,
        ]));

        return back()->with('status', 'Comissão vinculada ao processo.');
    }

    public function destroyComissao(Request $request, Comissao $comissao): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($comissao->processo_id, 404);

        $processo = $comissao->processo;
        $comissao->delete();

        return redirect()
            ->route('processos.show', $processo)
            ->with('status', 'Comissão removida.');
    }

    public function storeNegociacao(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'data' => ['required', 'date'],
            'assessoria' => ['nullable', 'string', 'max:120'],
            'resumo' => ['required', 'string', 'max:2000'],
            'val_atualizado' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'val_analise' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'val_em_maos' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($processo, $data, $request) {
            Negociacao::create(array_merge($data, [
                'processo_id' => $processo->id,
                'inserida_por_user_id' => $request->user()->id,
            ]));

            // Registra no histórico de acompanhamento pra ficar visível na timeline lateral.
            $resumoCurto = \Illuminate\Support\Str::limit($data['resumo'], 140);
            $obs = 'Nova negociação registrada';
            if ($data['assessoria'] ?? null) $obs .= ' (' . $data['assessoria'] . ')';
            $obs .= ': ' . $resumoCurto;

            HistoricoProcesso::create([
                'processo_id' => $processo->id,
                'user_id' => $request->user()->id,
                'status_anterior' => $processo->status,
                'status_novo' => $processo->status, // não muda status
                'observacao' => $obs,
            ]);
        });

        return back()->with('status', 'Negociação registrada.');
    }

    public function destroyNegociacao(Request $request, Negociacao $negociacao): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $processo = $negociacao->processo;
        $negociacao->delete();

        return redirect()
            ->route('processos.show', $processo)
            ->with('status', 'Negociação removida.');
    }

    private function authorizeAccess(Request $request, Processo $processo): void
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isOwner = $processo->user_id === $user->id;
        // Comprador vinculado ao processo também tem acesso à visualização
        $isCompradorVinculado = $user->hasRole('comprador')
            && $processo->comprador
            && $processo->comprador->user_id === $user->id;
        abort_unless($isAdmin || $isOwner || $isCompradorVinculado, 403);
    }

    private function authorizeOwner(Request $request, Processo $processo): void
    {
        abort_unless($processo->user_id === $request->user()->id, 403);
    }

    private function clientesParaSelect()
    {
        return User::role(['mentorado', 'licenciado'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function validateProcesso(Request $request, bool $isAdmin): array
    {
        $combustiveis = implode(',', array_keys(\App\Models\Veiculo::COMBUSTIVEIS));

        $rules = [
            'nome_completo' => ['required', 'string', 'max:160'],
            'tipo_documento' => ['required', 'in:cpf,cnpj'],
            'documento' => ['required', 'string', 'max:20'],
            'email_contato' => ['nullable', 'email', 'max:160'],
            'telefone_contato' => ['nullable', 'string', 'max:40'],
            'servico_id' => ['required', 'exists:servicos,id'],
            'observacoes_cliente' => ['nullable', 'string', 'max:3000'],

            // Endereço (todo opcional)
            'cep' => ['nullable', 'string', 'max:10'],
            'logradouro' => ['nullable', 'string', 'max:160'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:80'],
            'bairro' => ['nullable', 'string', 'max:80'],
            'cidade' => ['nullable', 'string', 'max:80'],
            'uf' => ['nullable', 'string', 'size:2'],

            'dividas' => ['array'],
            'dividas.*.credor' => ['required_with:dividas.*.valor', 'string', 'max:160'],
            'dividas.*.valor' => ['required_with:dividas.*.credor', 'numeric', 'min:0'],
            'dividas.*.descricao' => ['nullable', 'string', 'max:500'],

            // Veículo (todo opcional; salvo apenas se algum campo relevante vier preenchido)
            'veiculo' => ['nullable', 'array'],
            'veiculo.placa' => ['nullable', 'string', 'max:10'],
            'veiculo.marca' => ['nullable', 'string', 'max:60'],
            'veiculo.modelo' => ['nullable', 'string', 'max:100'],
            'veiculo.ano_fabricacao' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'veiculo.ano_modelo' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 2)],
            'veiculo.cor' => ['nullable', 'string', 'max:30'],
            'veiculo.chassi' => ['nullable', 'string', 'max:20'],
            'veiculo.renavam' => ['nullable', 'string', 'max:20'],
            'veiculo.combustivel' => ['nullable', 'in:' . $combustiveis],
            'veiculo.quilometragem' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'veiculo.valor_fipe' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'veiculo.observacoes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($isAdmin) {
            $rules['user_id'] = ['required', 'exists:users,id'];
            $rules['comprador_id'] = ['nullable', 'exists:compradores,id'];
            $rules['observacoes_admin'] = ['nullable', 'string', 'max:5000'];
        }

        $data = $request->validate($rules);

        $processo = collect($data)->except(['dividas', 'veiculo'])->all();
        $processo['documento'] = preg_replace('/\D/', '', (string) $processo['documento']);
        if (isset($processo['cep'])) {
            $processo['cep'] = preg_replace('/\D/', '', (string) $processo['cep']) ?: null;
        }
        if (isset($processo['uf'])) {
            $processo['uf'] = strtoupper((string) $processo['uf']) ?: null;
        }

        $dividas = collect($data['dividas'] ?? [])
            ->filter(fn ($d) => ! empty($d['credor']))
            ->map(fn ($d) => [
                'credor' => $d['credor'],
                'valor' => $d['valor'] ?? 0,
                'descricao' => $d['descricao'] ?? null,
            ])
            ->values()
            ->all();

        // Veículo: só considera "preenchido" se pelo menos um campo identificador vier
        $veiculoInput = $data['veiculo'] ?? [];
        $veiculo = null;
        $temIdentificador = collect(['placa', 'marca', 'modelo', 'chassi', 'renavam'])
            ->contains(fn ($k) => ! empty($veiculoInput[$k] ?? null));
        if ($temIdentificador) {
            $veiculo = $veiculoInput;
            if (isset($veiculo['placa'])) {
                $veiculo['placa'] = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $veiculo['placa']))) ?: null;
            }
        }

        return ['processo' => $processo, 'dividas' => $dividas, 'veiculo' => $veiculo];
    }
}
