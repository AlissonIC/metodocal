<?php

namespace App\Http\Controllers;

use App\Models\DocumentoLimpaNome;
use App\Models\HistoricoLimpaNome;
use App\Models\ProcessoLimpaNome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ProcessoLimpaNomeController extends Controller
{
    public function index()
    {
        return view('content.limpa-nome.index', [
            'statuses' => ProcessoLimpaNome::STATUSES,
            'tipos' => ProcessoLimpaNome::TIPOS,
            'isAdmin' => auth()->user()->can('access.limpa-nome.manage'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $isAdmin = $request->user()->can('access.limpa-nome.manage');

        $query = ProcessoLimpaNome::query()->withCount('documentos');

        if ($isAdmin) {
            $query->with('user:id,name');
        } else {
            $query->where('user_id', $request->user()->id);
        }

        if ($s = $request->query('status')) $query->where('status', $s);
        if ($t = $request->query('tipo')) $query->where('tipo', $t);
        if ($isAdmin && ($c = trim((string) $request->query('cliente', '')))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $c . '%'));
        }

        return DataTables::eloquent($query->orderByDesc('created_at'))
            ->addColumn('cliente', fn (ProcessoLimpaNome $p) => $p->user?->name ?? '—')
            ->addColumn('tipo_label', fn (ProcessoLimpaNome $p) => $p->tipoLabel())
            ->addColumn('documento_formatado', fn (ProcessoLimpaNome $p) => strtoupper($p->tipo_documento) . ': ' . $p->documento)
            ->addColumn('status_badge', fn (ProcessoLimpaNome $p) =>
                '<span class="badge bg-label-' . $p->statusColor() . '">' . e($p->statusLabel()) . '</span>')
            ->addColumn('criado_em', fn (ProcessoLimpaNome $p) => $p->created_at?->format('d/m/Y H:i'))
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create(Request $request)
    {
        abort_if($request->user()->cannot('access.limpa-nome.view') || $this->isAdminOnly($request), 403);

        return view('content.limpa-nome.form', [
            'processo' => new ProcessoLimpaNome(['tipo' => 'limpa_nome', 'tipo_documento' => 'cpf']),
            'dividas' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($this->isAdminOnly($request), 403, 'Administradores não cadastram processos.');

        $data = $this->validateProcesso($request);

        $processo = DB::transaction(function () use ($data, $request) {
            $processo = ProcessoLimpaNome::create(array_merge($data['processo'], [
                'user_id' => $request->user()->id,
                'status' => 'cadastrado',
            ]));

            foreach ($data['dividas'] as $divida) {
                $processo->dividas()->create($divida);
            }

            HistoricoLimpaNome::create([
                'processo_id' => $processo->id,
                'user_id' => $request->user()->id,
                'status_anterior' => null,
                'status_novo' => 'cadastrado',
                'observacao' => 'Processo cadastrado pelo cliente.',
            ]);

            return $processo;
        });

        return redirect()
            ->route('limpa-nome.show', $processo)
            ->with('status', 'Processo cadastrado com sucesso.');
    }

    public function show(Request $request, ProcessoLimpaNome $processo)
    {
        $this->authorizeAccess($request, $processo);

        $processo->load(['user:id,name,email', 'dividas', 'documentos.uploadedBy:id,name', 'historico.user:id,name']);

        return view('content.limpa-nome.show', [
            'processo' => $processo,
            'statuses' => ProcessoLimpaNome::STATUSES,
            'isAdmin' => $request->user()->can('access.limpa-nome.manage'),
            'isOwner' => $processo->user_id === $request->user()->id,
        ]);
    }

    public function edit(Request $request, ProcessoLimpaNome $processo)
    {
        $this->authorizeOwner($request, $processo);
        abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser editado.');

        return view('content.limpa-nome.form', [
            'processo' => $processo,
            'dividas' => $processo->dividas,
        ]);
    }

    public function update(Request $request, ProcessoLimpaNome $processo): RedirectResponse
    {
        $this->authorizeOwner($request, $processo);
        abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser editado.');

        $data = $this->validateProcesso($request);

        DB::transaction(function () use ($processo, $data) {
            $processo->update($data['processo']);
            $processo->dividas()->delete();
            foreach ($data['dividas'] as $divida) {
                $processo->dividas()->create($divida);
            }
        });

        return redirect()
            ->route('limpa-nome.show', $processo)
            ->with('status', 'Processo atualizado.');
    }

    public function destroy(Request $request, ProcessoLimpaNome $processo): RedirectResponse
    {
        $this->authorizeOwner($request, $processo);
        abort_unless($processo->isEditavelPeloCliente(), 403, 'Processo não pode mais ser excluído.');

        foreach ($processo->documentos as $doc) {
            Storage::disk('local')->delete($doc->arquivo);
        }
        $processo->delete();

        return redirect()
            ->route('limpa-nome.index')
            ->with('status', 'Processo excluído.');
    }

    public function uploadDocumento(Request $request, ProcessoLimpaNome $processo): RedirectResponse
    {
        $this->authorizeAccess($request, $processo);

        $request->validate([
            'arquivo' => ['required', 'file', 'max:20480'],
            'categoria' => ['nullable', 'string', 'max:80'],
        ]);

        $file = $request->file('arquivo');
        $path = $file->store('limpa-nome/' . $processo->id, 'local');

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

    public function destroyDocumento(Request $request, DocumentoLimpaNome $documento): RedirectResponse
    {
        $this->authorizeAccess($request, $documento->processo);

        $isAdmin = $request->user()->can('access.limpa-nome.manage');
        $isUploader = $documento->uploaded_by_user_id === $request->user()->id;
        abort_unless($isAdmin || $isUploader, 403, 'Você só pode excluir documentos que enviou.');

        Storage::disk('local')->delete($documento->arquivo);
        $documento->delete();

        return back()->with('status', 'Documento excluído.');
    }

    public function downloadDocumento(Request $request, DocumentoLimpaNome $documento): StreamedResponse
    {
        $this->authorizeAccess($request, $documento->processo);

        return Storage::disk('local')->download($documento->arquivo, $documento->nome_original);
    }

    public function updateStatus(Request $request, ProcessoLimpaNome $processo): RedirectResponse
    {
        abort_unless($request->user()->can('access.limpa-nome.manage'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(ProcessoLimpaNome::STATUSES))],
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

        HistoricoLimpaNome::create([
            'processo_id' => $processo->id,
            'user_id' => $request->user()->id,
            'status_anterior' => $anterior,
            'status_novo' => $data['status'],
            'observacao' => $data['observacao'] ?? null,
        ]);

        return back()->with('status', 'Status atualizado.');
    }

    public function updateObservacoes(Request $request, ProcessoLimpaNome $processo): RedirectResponse
    {
        abort_unless($request->user()->can('access.limpa-nome.manage'), 403);

        $data = $request->validate([
            'observacoes_admin' => ['nullable', 'string', 'max:5000'],
        ]);

        $processo->update($data);

        return back()->with('status', 'Observações salvas.');
    }

    private function authorizeAccess(Request $request, ProcessoLimpaNome $processo): void
    {
        $isAdmin = $request->user()->can('access.limpa-nome.manage');
        $isOwner = $processo->user_id === $request->user()->id;
        abort_unless($isAdmin || $isOwner, 403);
    }

    private function authorizeOwner(Request $request, ProcessoLimpaNome $processo): void
    {
        abort_unless($processo->user_id === $request->user()->id, 403);
    }

    private function isAdminOnly(Request $request): bool
    {
        return $request->user()->can('access.limpa-nome.manage')
            && ! $request->user()->can('access.limpa-nome.view');
    }

    private function validateProcesso(Request $request): array
    {
        $data = $request->validate([
            'nome_completo' => ['required', 'string', 'max:160'],
            'tipo_documento' => ['required', 'in:cpf,cnpj'],
            'documento' => ['required', 'string', 'max:20'],
            'email_contato' => ['nullable', 'email', 'max:160'],
            'telefone_contato' => ['nullable', 'string', 'max:40'],
            'tipo' => ['required', 'in:limpa_nome,aquisicao,negociacao_divida'],
            'observacoes_cliente' => ['nullable', 'string', 'max:3000'],
            'dividas' => ['array'],
            'dividas.*.credor' => ['required_with:dividas.*.valor', 'string', 'max:160'],
            'dividas.*.valor' => ['required_with:dividas.*.credor', 'numeric', 'min:0'],
            'dividas.*.descricao' => ['nullable', 'string', 'max:500'],
        ]);

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
