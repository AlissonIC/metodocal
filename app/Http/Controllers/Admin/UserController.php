<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Comprador;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    private const ROLE_COLORS = [
        'admin' => 'primary',
        'mentorado' => 'info',
        'licenciado' => 'warning',
        'comprador' => 'success',
    ];

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
            ->with(['roles:id,name', 'currentSubscription.plan:id,nome,tipo,preco,recorrencia'])
            ->latest('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filtro de roles: aceita array (multi-select) OU string única (compat)
        $roles = (array) $request->query('roles', []);
        if (empty($roles) && ($single = $request->query('role'))) {
            $roles = [$single];
        }
        $roles = array_values(array_filter($roles, fn ($r) => is_string($r) && $r !== ''));
        if (! empty($roles)) {
            $query->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
        }

        if ($planId = $request->query('plan_id')) {
            $query->whereHas('currentSubscription', fn ($s) => $s->where('plan_id', $planId));
        }

        return DataTables::eloquent($query)
            ->addColumn('role', function (User $u) {
                $role = $u->getRoleNames()->first() ?? '—';
                $c = self::ROLE_COLORS[$role] ?? 'secondary';
                return '<span class="badge bg-label-' . $c . '">' . ucfirst($role) . '</span>';
            })
            ->addColumn('plano', function (User $u) {
                return $u->currentSubscription?->plan?->nome ?? '<span class="text-muted">—</span>';
            })
            ->addColumn('status_badge', function (User $u) {
                $map = ['ativo' => 'success', 'inativo' => 'secondary', 'bloqueado' => 'danger'];
                $color = $map[$u->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . ucfirst($u->status) . '</span>';
            })
            ->addColumn('criado_em', fn (User $u) => $u->created_at?->format('d/m/Y'))
            ->addColumn('actions', fn (User $u) => $u->id)
            ->addColumn('details', function (User $u) {
                $sub = $u->currentSubscription;
                return [
                    'name'              => $u->name,
                    'email'             => $u->email,
                    'phone'             => $u->phone,
                    'cpf_cnpj'          => $u->cpf_cnpj,
                    'role'              => $u->getRoleNames()->first(),
                    'status'            => $u->status,
                    'created_at'        => $u->created_at?->format('d/m/Y H:i'),
                    'email_verified_at' => $u->email_verified_at?->format('d/m/Y H:i'),
                    'last_login_at'     => $u->last_login_at?->format('d/m/Y H:i'),
                    'endereco'          => $u->cep ? trim("{$u->logradouro}, {$u->numero} · {$u->bairro} · {$u->cidade}/{$u->uf} · CEP {$u->cep}", ' ,·') : null,
                    'plan_nome'         => $sub?->plan?->nome,
                    'plan_tipo'         => $sub?->plan?->tipo,
                    'plan_preco'        => $sub?->plan?->preco,
                    'plan_recorrencia'  => $sub?->plan?->recorrencia,
                    'sub_status'        => $sub?->status,
                    'sub_started_at'    => $sub?->started_at?->format('d/m/Y'),
                    'sub_ends_at'       => $sub?->ends_at?->format('d/m/Y'),
                ];
            })
            ->filterColumn('role', function ($q, $kw) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'like', "%$kw%"));
            })
            ->rawColumns(['role', 'plano', 'status_badge'])
            ->toJson();
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('content.admin.users.form', [
            'user' => new User(),
            'plans' => Plan::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']),
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $user->load(['currentSubscription:id,plan_id', 'comprador']);

        return view('content.admin.users.form', [
            'user' => $user,
            'plans' => Plan::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $planId = $data['plan_id'] ?? null;
        $role = $data['role'];
        unset($data['plan_id'], $data['role']);

        DB::transaction(function () use ($data, $role, $planId) {
            $user = User::create(array_merge(
                collect($data)->except(['password'])->all(),
                [
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ]
            ));
            $user->assignRole($role);

            if ($planId) {
                $this->attachSubscription($user, (int) $planId);
            }

            $this->syncComprador($user, $role);
        });

        return redirect()
            ->route('admin.users')
            ->with('status', 'Usuário criado com sucesso.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        $planId = array_key_exists('plan_id', $data) ? $data['plan_id'] : null;
        $role = $data['role'];
        unset($data['plan_id'], $data['role']);

        DB::transaction(function () use ($user, $data, $role, $planId) {
            $update = collect($data)->except(['password'])->all();
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

            $this->syncComprador($user, $role);
        });

        return redirect()
            ->route('admin.users')
            ->with('status', 'Usuário atualizado com sucesso.');
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

        // Se for comprador com processos vinculados, bloqueia
        $comprador = $user->comprador;
        if ($comprador && $comprador->processos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Este usuário é comprador em processos existentes. Desvincule os processos antes.',
            ], 422);
        }

        DB::transaction(function () use ($user, $comprador) {
            $comprador?->delete();
            $user->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário excluído.',
        ]);
    }

    /**
     * Mantém a linha em `compradores` em sincronia com o User quando a role é 'comprador'.
     * Se a role mudou para outra coisa e existia um comprador SEM processos, apaga-o.
     */
    private function syncComprador(User $user, string $role): void
    {
        $existing = $user->comprador()->first();

        if ($role === 'comprador') {
            $payload = [
                'nome' => $user->name,
                'email' => $user->email,
                'telefone' => $user->phone,
                'tipo_documento' => $user->tipo_documento ?: 'cpf',
                'documento' => $user->cpf_cnpj,
                'observacoes' => $user->observacoes,
                'ativo' => $user->status === 'ativo',
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                Comprador::create(array_merge($payload, ['user_id' => $user->id]));
            }
            return;
        }

        // Role mudou; só remove o comprador se ele não tiver processos, senão mantém desvinculado da role.
        if ($existing && ! $existing->processos()->exists()) {
            $existing->delete();
        }
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
