<?php

namespace App\Http\Controllers;

use App\Models\Comissao;
use App\Models\Comprador;
use App\Models\DocumentoProcesso;
use App\Models\Fatura;
use App\Models\HistoricoProcesso;
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
        $isAdmin = $request->user()->hasRole('admin');

        $query = Processo::query()
            ->with('servico:id,nome')
            ->withCount('documentos');

        if ($isAdmin) {
            $query->with('user:id,name');
        } else {
            $query->where('user_id', $request->user()->id);
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

        $relations = [
            'user:id,name,email',
            'servico:id,nome',
            'dividas',
            'documentos.uploadedBy:id,name',
            'historico.user:id,name',
            'faturas',
        ];
        $isAdmin = $request->user()->hasRole('admin');
        if ($isAdmin) {
            $relations[] = 'comprador';
            $relations[] = 'comissoes.licenciado:id,name';
        }
        $processo->load($relations);

        return view('content.processos.show', [
            'processo' => $processo,
            'statuses' => Processo::STATUSES,
            'isAdmin' => $isAdmin,
            'isOwner' => $processo->user_id === $request->user()->id,
            'usuariosParaComissao' => $isAdmin ? User::orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ]);
    }

    public function edit(Request $request, Processo $processo)
    {
        $isAdmin = $request->user()->hasRole('admin');
        if (! $isAdmin) {
            $this->authorizeOwner($request, $processo);
            abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser editado.');
        }

        return view('content.processos.form', [
            'processo' => $processo,
            'dividas' => $processo->dividas,
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

    private function authorizeAccess(Request $request, Processo $processo): void
    {
        $isAdmin = $request->user()->hasRole('admin');
        $isOwner = $processo->user_id === $request->user()->id;
        abort_unless($isAdmin || $isOwner, 403);
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
        $rules = [
            'nome_completo' => ['required', 'string', 'max:160'],
            'tipo_documento' => ['required', 'in:cpf,cnpj'],
            'documento' => ['required', 'string', 'max:20'],
            'email_contato' => ['nullable', 'email', 'max:160'],
            'telefone_contato' => ['nullable', 'string', 'max:40'],
            'servico_id' => ['required', 'exists:servicos,id'],
            'observacoes_cliente' => ['nullable', 'string', 'max:3000'],
            'dividas' => ['array'],
            'dividas.*.credor' => ['required_with:dividas.*.valor', 'string', 'max:160'],
            'dividas.*.valor' => ['required_with:dividas.*.credor', 'numeric', 'min:0'],
            'dividas.*.descricao' => ['nullable', 'string', 'max:500'],
        ];

        if ($isAdmin) {
            $rules['user_id'] = ['required', 'exists:users,id'];
            $rules['comprador_id'] = ['nullable', 'exists:compradores,id'];
            $rules['observacoes_admin'] = ['nullable', 'string', 'max:5000'];
        }

        $data = $request->validate($rules);

        $processo = collect($data)->except('dividas')->all();
        $processo['documento'] = preg_replace('/\D/', '', (string) $processo['documento']);

        $dividas = collect($data['dividas'] ?? [])
            ->filter(fn ($d) => ! empty($d['credor']))
            ->map(fn ($d) => [
                'credor' => $d['credor'],
                'valor' => $d['valor'] ?? 0,
                'descricao' => $d['descricao'] ?? null,
            ])
            ->values()
            ->all();

        return ['processo' => $processo, 'dividas' => $dividas];
    }
}
