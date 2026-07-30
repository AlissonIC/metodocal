<?php

namespace App\Http\Controllers;

use App\Models\Comissao;
use App\Models\Comprador;
use App\Models\DocumentoProcesso;
use App\Models\Fatura;
use App\Models\HistoricoProcesso;
use App\Models\Negociacao;
use App\Models\ObservacaoProcesso;
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
        $user = auth()->user();
        return view('content.processos.index', [
            'statuses' => Processo::STATUSES,
            'servicos' => Servico::orderBy('nome')->get(['id', 'nome']),
            'isAdmin' => $user->hasRole('admin'),
            'isComprador' => $user->hasRole('comprador'),
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

            $this->logAtividade(
                $processo,
                HistoricoProcesso::ACAO_CREATED,
                'processo',
                $processo->id,
                $isAdmin ? 'Processo cadastrado pelo administrador.' : 'Processo cadastrado pelo cliente.',
                null,
                'cadastrado',
            );

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

            $this->logAtividade($processo, HistoricoProcesso::ACAO_UPDATED, 'processo', $processo->id, 'Dados do processo atualizados.');
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

        $doc = $processo->documentos()->create([
            'uploaded_by_user_id' => $request->user()->id,
            'categoria' => $request->input('categoria') ?: null,
            'nome_original' => $file->getClientOriginalName(),
            'arquivo' => $path,
            'tamanho_bytes' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        $this->logAtividade($processo, HistoricoProcesso::ACAO_CREATED, 'documento', $doc->id, 'Enviou o documento "' . $doc->nome_original . '".');

        return back()->with('status', 'Documento enviado.');
    }

    public function destroyDocumento(Request $request, DocumentoProcesso $documento): RedirectResponse
    {
        $this->authorizeAccess($request, $documento->processo);

        $isAdmin = $request->user()->hasRole('admin');
        $isUploader = $documento->uploaded_by_user_id === $request->user()->id;
        abort_unless($isAdmin || $isUploader, 403, 'Você só pode excluir documentos que enviou.');

        $nome = $documento->nome_original;
        $processo = $documento->processo;

        Storage::disk('local')->delete($documento->arquivo);
        $documento->delete();

        $this->logAtividade($processo, HistoricoProcesso::ACAO_DELETED, 'documento', $documento->id, 'Removeu o documento "' . $nome . '".');

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

        $anteriorLabel = Processo::STATUSES[$anterior][0] ?? $anterior ?? '—';
        $novoLabel = Processo::STATUSES[$data['status']][0] ?? $data['status'];
        $desc = 'Alterou status: "' . $anteriorLabel . '" → "' . $novoLabel . '"';
        if (! empty($data['observacao'])) $desc .= '. ' . $data['observacao'];

        $this->logAtividade(
            $processo,
            HistoricoProcesso::ACAO_STATUS,
            'status',
            null,
            $desc,
            $anterior,
            $data['status'],
        );

        return back()->with('status', 'Status atualizado.');
    }

    public function datatableObservacoes(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = ObservacaoProcesso::query()
            ->where('processo_id', $processo->id)
            ->with(['inseridaPor:id,name', 'editadaPor:id,name']);

        return DataTables::eloquent($query->orderByDesc('created_at'))
            ->addColumn('inserida_por_nome', fn (ObservacaoProcesso $o) => $o->inseridaPor?->name ?? '—')
            ->addColumn('editada_por_nome', fn (ObservacaoProcesso $o) => $o->editadaPor?->name ?? '—')
            ->addColumn('criada_em', fn (ObservacaoProcesso $o) => $o->created_at?->format('d/m/Y H:i'))
            ->addColumn('editada_em_formatada', fn (ObservacaoProcesso $o) => $o->editada_em?->format('d/m/Y H:i'))
            ->addColumn('tem_anexo', fn (ObservacaoProcesso $o) => $o->hasAnexo())
            ->addColumn('anexo_is_image', fn (ObservacaoProcesso $o) => $o->anexoIsImage())
            ->toJson();
    }

    public function storeObservacao(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateObservacao($request);

        $obs = ObservacaoProcesso::create([
            'processo_id' => $processo->id,
            'inserida_por_user_id' => $request->user()->id,
            'resumo' => $data['resumo'],
            'descricao' => $data['descricao'],
        ]);

        if ($file = $request->file('anexo')) {
            $this->attachFile($obs, $file, $processo);
        }

        $this->logAtividade($processo, HistoricoProcesso::ACAO_CREATED, 'observacao', $obs->id, 'Criou observação: "' . $obs->resumo . '"' . ($obs->hasAnexo() ? ' (+ anexo)' : ''));

        return response()->json(['message' => 'Observação registrada.', 'id' => $obs->id]);
    }

    public function showObservacao(Request $request, ObservacaoProcesso $observacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $observacao->load(['inseridaPor:id,name', 'editadaPor:id,name']);

        return response()->json([
            'id' => $observacao->id,
            'resumo' => $observacao->resumo,
            'descricao' => $observacao->descricao,
            'inserida_por' => $observacao->inseridaPor?->name,
            'editada_por' => $observacao->editadaPor?->name,
            'criada_em' => $observacao->created_at?->format('d/m/Y H:i'),
            'editada_em' => $observacao->editada_em?->format('d/m/Y H:i'),
            'anexo' => $observacao->hasAnexo() ? [
                'nome' => $observacao->anexo_nome_original,
                'mime' => $observacao->anexo_mime,
                'tamanho' => $observacao->anexoTamanhoFormatado(),
                'is_image' => $observacao->anexoIsImage(),
                'url' => route('processos.observacoes.anexo.download', $observacao),
            ] : null,
        ]);
    }

    public function updateObservacao(Request $request, ObservacaoProcesso $observacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateObservacao($request);

        $observacao->update([
            'resumo' => $data['resumo'],
            'descricao' => $data['descricao'],
            'editada_por_user_id' => $request->user()->id,
            'editada_em' => now(),
        ]);

        // Remoção explícita do anexo existente (usuário clicou "Remover anexo")
        if ($request->boolean('remover_anexo') && $observacao->hasAnexo()) {
            $this->detachFile($observacao);
        }

        // Upload de novo anexo (substitui o anterior, se houver)
        if ($file = $request->file('anexo')) {
            if ($observacao->hasAnexo()) $this->detachFile($observacao);
            $this->attachFile($observacao, $file, $observacao->processo);
        }

        $this->logAtividade($observacao->processo, HistoricoProcesso::ACAO_UPDATED, 'observacao', $observacao->id, 'Editou observação: "' . $observacao->resumo . '"');

        return response()->json(['message' => 'Observação atualizada.']);
    }

    public function destroyObservacao(Request $request, ObservacaoProcesso $observacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $resumo = $observacao->resumo;
        $processo = $observacao->processo;

        if ($observacao->hasAnexo()) {
            Storage::disk('local')->delete($observacao->anexo_arquivo);
        }
        $observacao->delete();

        $this->logAtividade($processo, HistoricoProcesso::ACAO_DELETED, 'observacao', $observacao->id, 'Removeu observação: "' . $resumo . '"');

        return response()->json(['message' => 'Observação excluída.']);
    }

    public function downloadAnexoObservacao(Request $request, ObservacaoProcesso $observacao): StreamedResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($observacao->hasAnexo() && Storage::disk('local')->exists($observacao->anexo_arquivo), 404);

        return Storage::disk('local')->download($observacao->anexo_arquivo, $observacao->anexo_nome_original);
    }

    private function validateObservacao(Request $request): array
    {
        return $request->validate([
            'resumo' => ['required', 'string', 'max:200'],
            'descricao' => ['required', 'string', 'max:5000'],
            'anexo' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip'],
        ]);
    }

    private function attachFile(ObservacaoProcesso $obs, \Illuminate\Http\UploadedFile $file, Processo $processo): void
    {
        $path = $file->store('observacoes/' . $processo->id, 'local');
        $obs->update([
            'anexo_arquivo' => $path,
            'anexo_nome_original' => $file->getClientOriginalName(),
            'anexo_mime' => $file->getMimeType(),
            'anexo_tamanho_bytes' => $file->getSize(),
        ]);
    }

    private function detachFile(ObservacaoProcesso $obs): void
    {
        if ($obs->anexo_arquivo) {
            Storage::disk('local')->delete($obs->anexo_arquivo);
        }
        $obs->update([
            'anexo_arquivo' => null,
            'anexo_nome_original' => null,
            'anexo_mime' => null,
            'anexo_tamanho_bytes' => null,
        ]);
    }

    public function datatableFaturas(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = Fatura::query()->where('processo_id', $processo->id);

        return DataTables::eloquent($query->orderByDesc('vencimento')->orderByDesc('id'))
            ->addColumn('descricao_fmt', fn (Fatura $f) => $f->descricao ?: 'Cobrança do processo')
            ->addColumn('valor_fmt', fn (Fatura $f) => 'R$ ' . number_format((float) $f->valor, 2, ',', '.'))
            ->addColumn('vencimento_fmt', fn (Fatura $f) => $f->vencimento?->format('d/m/Y'))
            ->addColumn('status_badge', function (Fatura $f) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary', 'estornada' => 'info', 'atrasada' => 'danger'];
                $color = $f->isAtrasada() ? 'danger' : ($map[$f->status] ?? 'secondary');
                $label = $f->isAtrasada() ? 'Atrasada' : ucfirst($f->status);
                return '<span class="badge bg-label-' . $color . '">' . e($label) . '</span>';
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function showFatura(Request $request, Fatura $fatura): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($fatura->processo_id, 404);

        return response()->json([
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor' => number_format((float) $fatura->valor, 2, ',', '.'),
            'vencimento' => $fatura->vencimento?->toDateString(),
            'status' => $fatura->status,
        ]);
    }

    public function storeFatura(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'vencimento' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);

        $processo->loadMissing('user:id,name,email,cpf_cnpj');

        $fatura = Fatura::create([
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

        $this->logAtividade($processo, HistoricoProcesso::ACAO_CREATED, 'fatura', $fatura->id, 'Criou dívida de R$ ' . number_format((float) $fatura->valor, 2, ',', '.') . ($fatura->descricao ? ' (' . $fatura->descricao . ')' : ''));

        return response()->json(['message' => 'Fatura registrada.']);
    }

    public function updateFatura(Request $request, Fatura $fatura): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($fatura->processo_id, 404);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'vencimento' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);

        $updates = [
            'descricao' => $data['descricao'] ?? null,
            'valor' => $data['valor'],
            'vencimento' => $data['vencimento'],
            'status' => $data['status'],
        ];

        // Ajusta pago_em/metodo apenas quando o status entra/sai de "paga" via edição manual
        if ($data['status'] === 'paga' && $fatura->status !== 'paga') {
            $updates['pago_em'] = now();
            $updates['metodo'] = 'manual';
        } elseif ($data['status'] !== 'paga' && $fatura->status === 'paga') {
            $updates['pago_em'] = null;
        }

        $fatura->update($updates);

        $this->logAtividade($fatura->processo, HistoricoProcesso::ACAO_UPDATED, 'fatura', $fatura->id, 'Editou dívida de R$ ' . number_format((float) $fatura->valor, 2, ',', '.') . ' (status: ' . ucfirst($fatura->status) . ')');

        return response()->json(['message' => 'Fatura atualizada.']);
    }

    public function destroyFatura(Request $request, Fatura $fatura): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($fatura->processo_id, 404);

        $valor = number_format((float) $fatura->valor, 2, ',', '.');
        $processo = $fatura->processo;
        $fatura->delete();

        $this->logAtividade($processo, HistoricoProcesso::ACAO_DELETED, 'fatura', $fatura->id, 'Removeu dívida de R$ ' . $valor);

        return response()->json(['message' => 'Fatura excluída.']);
    }

    public function datatableComissoes(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = Comissao::query()
            ->where('processo_id', $processo->id)
            ->with('licenciado:id,name');

        return DataTables::eloquent($query->orderByDesc('data_referencia')->orderByDesc('id'))
            ->addColumn('descricao_fmt', fn (Comissao $c) => $c->descricao)
            ->addColumn('usuario_nome', fn (Comissao $c) => $c->licenciado?->name ?? '—')
            ->addColumn('valor_fmt', fn (Comissao $c) => 'R$ ' . number_format((float) $c->valor, 2, ',', '.'))
            ->addColumn('tipo_badge', fn (Comissao $c) =>
                '<span class="badge bg-label-' . $c->tipoColor() . '">' . e($c->tipoLabel()) . '</span>')
            ->addColumn('data_fmt', fn (Comissao $c) => $c->data_referencia?->format('d/m/Y'))
            ->addColumn('status_badge', function (Comissao $c) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                $color = $map[$c->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . e(ucfirst($c->status)) . '</span>';
            })
            ->rawColumns(['tipo_badge', 'status_badge'])
            ->toJson();
    }

    public function showComissao(Request $request, Comissao $comissao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($comissao->processo_id, 404);

        return response()->json([
            'id' => $comissao->id,
            'licensed_by_user_id' => $comissao->licensed_by_user_id,
            'descricao' => $comissao->descricao,
            'valor' => number_format((float) $comissao->valor, 2, ',', '.'),
            'tipo' => $comissao->tipo,
            'data_referencia' => $comissao->data_referencia?->toDateString(),
            'status' => $comissao->status,
        ]);
    }

    public function storeComissao(Request $request, Processo $processo): JsonResponse
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

        $comissao = Comissao::create(array_merge($data, [
            'processo_id' => $processo->id,
            'pago_em' => $data['status'] === 'paga' ? now() : null,
        ]));

        $valorFmt = number_format((float) $comissao->valor, 2, ',', '.');
        $this->logAtividade($processo, HistoricoProcesso::ACAO_CREATED, 'comissao', $comissao->id, 'Criou comissão (' . $comissao->tipoLabel() . ') de R$ ' . $valorFmt . ': ' . $comissao->descricao);

        return response()->json(['message' => 'Comissão registrada.']);
    }

    public function updateComissao(Request $request, Comissao $comissao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($comissao->processo_id, 404);

        $data = $request->validate([
            'licensed_by_user_id' => ['required', 'exists:users,id'],
            'descricao' => ['required', 'string', 'max:160'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'tipo' => ['required', 'in:a_receber,a_pagar'],
            'data_referencia' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);

        $updates = $data;
        if ($data['status'] === 'paga' && $comissao->status !== 'paga') {
            $updates['pago_em'] = now();
        } elseif ($data['status'] !== 'paga' && $comissao->status === 'paga') {
            $updates['pago_em'] = null;
        }

        $comissao->update($updates);

        $valorFmt = number_format((float) $comissao->valor, 2, ',', '.');
        $this->logAtividade($comissao->processo, HistoricoProcesso::ACAO_UPDATED, 'comissao', $comissao->id, 'Editou comissão (' . $comissao->tipoLabel() . ') de R$ ' . $valorFmt);

        return response()->json(['message' => 'Comissão atualizada.']);
    }

    public function destroyComissao(Request $request, Comissao $comissao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($comissao->processo_id, 404);

        $valorFmt = number_format((float) $comissao->valor, 2, ',', '.');
        $processo = $comissao->processo;
        $desc = 'Removeu comissão (' . $comissao->tipoLabel() . ') de R$ ' . $valorFmt;
        $comissao->delete();

        $this->logAtividade($processo, HistoricoProcesso::ACAO_DELETED, 'comissao', $comissao->id, $desc);

        return response()->json(['message' => 'Comissão removida.']);
    }

    public function datatableNegociacoes(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = Negociacao::query()
            ->where('processo_id', $processo->id)
            ->with('inseridaPor:id,name');

        return DataTables::eloquent($query->orderByDesc('data')->orderByDesc('id'))
            ->addColumn('resumo_curto', fn (Negociacao $n) => \Illuminate\Support\Str::limit($n->resumo, 140))
            ->addColumn('assessoria_fmt', fn (Negociacao $n) => $n->assessoria)
            ->addColumn('data_fmt', fn (Negociacao $n) => $n->data?->format('d/m/Y'))
            ->addColumn('val_em_maos_fmt', fn (Negociacao $n) => $n->val_em_maos !== null ? 'R$ ' . number_format((float) $n->val_em_maos, 2, ',', '.') : null)
            ->addColumn('autor_nome', fn (Negociacao $n) => $n->inseridaPor?->name)
            ->toJson();
    }

    public function showNegociacao(Request $request, Negociacao $negociacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $negociacao->load('inseridaPor:id,name');

        return response()->json([
            'id' => $negociacao->id,
            'data' => $negociacao->data?->toDateString(),
            'assessoria' => $negociacao->assessoria,
            'resumo' => $negociacao->resumo,
            'val_atualizado' => $negociacao->val_atualizado !== null ? number_format((float) $negociacao->val_atualizado, 2, ',', '.') : '',
            'val_analise' => $negociacao->val_analise !== null ? number_format((float) $negociacao->val_analise, 2, ',', '.') : '',
            'val_em_maos' => $negociacao->val_em_maos !== null ? number_format((float) $negociacao->val_em_maos, 2, ',', '.') : '',
            'feedback' => $negociacao->feedback,
            'autor' => $negociacao->inseridaPor?->name,
            'criada_em' => $negociacao->created_at?->format('d/m/Y H:i'),
        ]);
    }

    public function storeNegociacao(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateNegociacao($request);

        DB::transaction(function () use ($processo, $data, $request) {
            $negociacao = Negociacao::create(array_merge($data, [
                'processo_id' => $processo->id,
                'inserida_por_user_id' => $request->user()->id,
            ]));

            $resumoCurto = \Illuminate\Support\Str::limit($data['resumo'], 140);
            $desc = 'Registrou negociação';
            if ($data['assessoria'] ?? null) $desc .= ' (' . $data['assessoria'] . ')';
            $desc .= ': ' . $resumoCurto;

            $this->logAtividade($processo, HistoricoProcesso::ACAO_CREATED, 'negociacao', $negociacao->id, $desc);
        });

        return response()->json(['message' => 'Negociação registrada.']);
    }

    public function updateNegociacao(Request $request, Negociacao $negociacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateNegociacao($request);

        $negociacao->update($data);

        $resumoCurto = \Illuminate\Support\Str::limit($negociacao->resumo, 100);
        $this->logAtividade($negociacao->processo, HistoricoProcesso::ACAO_UPDATED, 'negociacao', $negociacao->id, 'Editou negociação: ' . $resumoCurto);

        return response()->json(['message' => 'Negociação atualizada.']);
    }

    public function destroyNegociacao(Request $request, Negociacao $negociacao): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $resumoCurto = \Illuminate\Support\Str::limit($negociacao->resumo, 100);
        $processo = $negociacao->processo;
        $negociacao->delete();

        $this->logAtividade($processo, HistoricoProcesso::ACAO_DELETED, 'negociacao', $negociacao->id, 'Removeu negociação: ' . $resumoCurto);

        return response()->json(['message' => 'Negociação removida.']);
    }

    private function validateNegociacao(Request $request): array
    {
        return $request->validate([
            'data' => ['required', 'date'],
            'assessoria' => ['nullable', 'string', 'max:120'],
            'resumo' => ['required', 'string', 'max:2000'],
            'val_atualizado' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'val_analise' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'val_em_maos' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Registra uma linha no log de atividades do processo.
     * Auto-vincula user_id ao usuário autenticado — o log responde "quem, o quê, quando".
     */
    private function logAtividade(
        Processo $processo,
        string $acao,
        string $entidade,
        ?int $entId,
        string $descricao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
    ): void {
        HistoricoProcesso::create([
            'processo_id' => $processo->id,
            'user_id' => auth()->id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entId,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'observacao' => $descricao,
        ]);
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
