<?php

namespace App\Http\Controllers;

use App\Helpers\Estados;
use App\Models\EmpresaGuincho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EmpresaGuinchoController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->hasRole('admin');

        $ufsDisponiveis = EmpresaGuincho::query()
            ->when(! $isAdmin, fn ($q) => $q->where('ativo', true))
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        $estadosFiltro = $ufsDisponiveis
            ->mapWithKeys(fn ($uf) => [$uf => Estados::nome($uf) ?? $uf])
            ->sort()
            ->all();

        return view('content.empresas-guincho.index', [
            'estados' => Estados::LISTA,
            'estadosFiltro' => $estadosFiltro,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $isAdmin = $request->user()->hasRole('admin');

        $query = EmpresaGuincho::query();

        if (! $isAdmin) {
            $query->where('ativo', true);
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', strtoupper($estado));
        }

        $cidadeAtendida = trim((string) $request->query('cidade', ''));

        return DataTables::eloquent($query->orderByDesc('created_at'))
            ->filter(function ($q) use ($cidadeAtendida) {
                if ($cidadeAtendida !== '') {
                    $q->where('cidades_atendidas', 'like', '%' . $cidadeAtendida . '%');
                }
            }, true)
            ->addColumn('logo_html', fn (EmpresaGuincho $e) => $e->logo
                ? '<img src="' . asset('storage/' . $e->logo) . '" alt="logo" class="rounded" style="height:36px;max-width:80px;object-fit:contain;background:#f5f5f9;padding:2px;">'
                : '<span class="text-muted">—</span>')
            ->addColumn('estado_nome', fn (EmpresaGuincho $e) => Estados::nome($e->estado) ?? '—')
            ->addColumn('cidades_resumo', function (EmpresaGuincho $e) {
                $cidades = $e->cidades_atendidas ?? [];
                if (empty($cidades)) return '<span class="text-muted">—</span>';
                $primeiras = array_slice($cidades, 0, 3);
                $extra = count($cidades) - count($primeiras);
                $html = collect($primeiras)
                    ->map(fn ($c) => '<span class="badge bg-label-info me-1">' . e($c) . '</span>')
                    ->implode('');
                if ($extra > 0) $html .= '<span class="badge bg-label-secondary">+' . $extra . '</span>';
                return $html;
            })
            ->addColumn('contatos_html', function (EmpresaGuincho $e) {
                $items = [];
                if ($e->telefone) {
                    $items[] = '<a href="tel:' . preg_replace('/\D/', '', $e->telefone) . '" class="text-body d-block small"><i class="icon-base ti tabler-phone me-1 text-muted"></i>' . e($e->telefone) . '</a>';
                }
                if ($e->whatsapp) {
                    $items[] = '<a href="https://wa.me/' . preg_replace('/\D/', '', $e->whatsapp) . '" target="_blank" class="text-body d-block small"><i class="icon-base ti tabler-brand-whatsapp me-1 text-success"></i>' . e($e->whatsapp) . '</a>';
                }
                return $items ? implode('', $items) : '<span class="text-muted">—</span>';
            })
            ->addColumn('status_badge', fn (EmpresaGuincho $e) => $e->ativo
                ? '<span class="badge bg-label-success">Ativa</span>'
                : '<span class="badge bg-label-secondary">Inativa</span>')
            ->addColumn('actions', fn (EmpresaGuincho $e) => $e->id)
            ->addColumn('details', fn (EmpresaGuincho $e) => [
                'nome'              => $e->nome,
                'cnpj'              => $e->cnpj,
                'telefone'          => $e->telefone,
                'whatsapp'          => $e->whatsapp,
                'email'             => $e->email,
                'site'              => $e->site,
                'estado'            => $e->estado,
                'cidade'            => $e->cidade,
                'cep'               => $e->cep,
                'endereco'          => $e->endereco,
                'numero'            => $e->numero,
                'complemento'       => $e->complemento,
                'bairro'            => $e->bairro,
                'cidades_atendidas' => $e->cidades_atendidas ?? [],
                'descricao'         => $e->descricao,
                'ativo'             => $e->ativo,
            ])
            ->rawColumns(['logo_html', 'cidades_resumo', 'contatos_html', 'status_badge'])
            ->toJson();
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('content.empresas-guincho.form', [
            'empresa' => new EmpresaGuincho(['ativo' => true]),
            'estados' => Estados::LISTA,
        ]);
    }

    public function edit(Request $request, EmpresaGuincho $empresaGuincho)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('content.empresas-guincho.form', [
            'empresa' => $empresaGuincho,
            'estados' => Estados::LISTA,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateData($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('empresas-guincho', 'public');
        }

        EmpresaGuincho::create($data);

        return redirect()
            ->route('guincho.index')
            ->with('status', 'Empresa cadastrada.');
    }

    public function update(Request $request, EmpresaGuincho $empresaGuincho): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateData($request);

        if ($request->hasFile('logo')) {
            if ($empresaGuincho->logo) {
                Storage::disk('public')->delete($empresaGuincho->logo);
            }
            $data['logo'] = $request->file('logo')->store('empresas-guincho', 'public');
        }

        $empresaGuincho->update($data);

        return redirect()
            ->route('guincho.index')
            ->with('status', 'Empresa atualizada.');
    }

    public function destroy(Request $request, EmpresaGuincho $empresaGuincho): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if ($empresaGuincho->logo) {
            Storage::disk('public')->delete($empresaGuincho->logo);
        }
        $empresaGuincho->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa removida.',
        ]);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:160'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'site' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', 'string', 'in:' . implode(',', Estados::ufs())],
            'cidade' => ['nullable', 'string', 'max:120'],
            'cidades_atendidas' => ['nullable', 'string'],
            'cep' => ['nullable', 'string', 'max:10'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:80'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = (bool) ($data['ativo'] ?? false);
        $data['estado'] = isset($data['estado']) ? strtoupper($data['estado']) : null;
        $data['cnpj'] = isset($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : null;
        $data['cep'] = isset($data['cep']) ? preg_replace('/\D/', '', $data['cep']) : null;
        $data['cidades_atendidas'] = $this->parseCidades($data['cidades_atendidas'] ?? null);

        unset($data['logo']);

        return $data;
    }

    private function parseCidades(?string $raw): array
    {
        if (! $raw) return [];

        return collect(explode(',', $raw))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->unique(fn ($c) => mb_strtolower($c))
            ->values()
            ->all();
    }
}
