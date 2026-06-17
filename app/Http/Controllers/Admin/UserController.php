<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return view('content.admin.users.index', [
            'plans' => Plan::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['roles:id,name', 'currentSubscription.plan:id,nome']);

        return DataTables::eloquent($query)
            ->addColumn('role', function (User $u) {
                return $u->getRoleNames()->first() ?? '—';
            })
            ->addColumn('plano', function (User $u) {
                return $u->currentSubscription?->plan?->nome ?? '—';
            })
            ->addColumn('status_badge', function (User $u) {
                $map = ['ativo' => 'success', 'inativo' => 'secondary', 'bloqueado' => 'danger'];
                $color = $map[$u->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . ucfirst($u->status) . '</span>';
            })
            ->addColumn('actions', fn (User $u) => $u->id)
            ->filterColumn('role', function ($q, $kw) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'like', "%$kw%"));
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'cpf_cnpj' => $user->cpf_cnpj,
            'status' => $user->status,
            'role' => $user->getRoleNames()->first(),
            'plan_id' => $user->currentSubscription?->plan_id,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $planId = $data['plan_id'] ?? null;
        unset($data['plan_id']);
        $role = $data['role'];
        unset($data['role']);

        $user = DB::transaction(function () use ($data, $role, $planId) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                'status' => $data['status'],
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);

            if ($planId) {
                $this->attachSubscription($user, (int) $planId);
            }
            return $user;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário criado com sucesso.',
            'data' => $user,
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        $planId = array_key_exists('plan_id', $data) ? $data['plan_id'] : null;
        unset($data['plan_id']);
        $role = $data['role'];
        unset($data['role']);

        DB::transaction(function () use ($user, $data, $role, $planId, $request) {
            $update = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                'status' => $data['status'],
            ];
            if (! empty($data['password'])) {
                $update['password'] = $data['password'];
            }
            $user->update($update);
            $user->syncRoles([$role]);

            $current = $user->currentSubscription;
            $currentPlanId = $current?->plan_id;
            if ($planId !== $currentPlanId) {
                if ($current) {
                    $current->update(['status' => 'cancelada', 'canceled_at' => now()]);
                    $user->forceFill(['current_subscription_id' => null])->save();
                }
                if ($planId) {
                    $this->attachSubscription($user, (int) $planId);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário atualizado com sucesso.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não pode excluir sua própria conta.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário excluído.',
        ]);
    }

    private function attachSubscription(User $user, int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'ativa',
            'started_at' => now(),
            'ends_at' => $this->calcEndsAt($plan->recorrencia),
        ]);
        $user->forceFill(['current_subscription_id' => $sub->id])->save();
    }

    private function calcEndsAt(string $recorrencia): ?\Carbon\Carbon
    {
        return match ($recorrencia) {
            'mensal' => now()->addMonth(),
            'anual' => now()->addYear(),
            'vitalicio' => null,
            default => now()->addMonth(),
        };
    }
}
