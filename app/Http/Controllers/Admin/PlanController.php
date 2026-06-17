<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PlanController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Plan::class);

        return view('content.admin.plans.index', [
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        $query = Plan::query()
            ->withCount(['subscriptions as ativas_count' => fn ($q) => $q->where('status', 'ativa')]);

        return DataTables::eloquent($query)
            ->addColumn('preco_formatado', fn (Plan $plan) => 'R$ ' . number_format((float) $plan->preco, 2, ',', '.'))
            ->addColumn('tipo_label', fn (Plan $plan) => ucfirst($plan->tipo))
            ->addColumn('recorrencia_label', fn (Plan $plan) => ucfirst($plan->recorrencia))
            ->addColumn('status_badge', fn (Plan $plan) => $plan->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->addColumn('actions', fn (Plan $plan) => $plan->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function show(Plan $plan): JsonResponse
    {
        $this->authorize('view', $plan);
        $plan->permissions = $plan->permissions ?? [];
        return response()->json($plan);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['nome']);

        $plan = Plan::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Plano criado com sucesso.',
            'data' => $plan,
        ], 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $data = $request->validated();

        if ($plan->nome !== $data['nome']) {
            $data['slug'] = $this->uniqueSlug($data['nome'], $plan->id);
        }

        $plan->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Plano atualizado com sucesso.',
            'data' => $plan->fresh(),
        ]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->authorize('delete', $plan);

        if ($plan->subscriptions()->whereIn('status', ['ativa', 'pendente'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir um plano com assinaturas ativas ou pendentes.',
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Plano excluído.',
        ]);
    }

    private function uniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        $base = Str::slug($nome);
        $slug = $base;
        $i = 1;
        while (Plan::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
