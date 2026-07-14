<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompradorRequest;
use App\Http\Requests\Admin\UpdateCompradorRequest;
use App\Models\Comprador;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CompradorController extends Controller
{
    public function index()
    {
        return view('content.admin.compradores.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = Comprador::query()->withCount('processos');

        if ($request->filled('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        return DataTables::eloquent($query)
            ->addColumn('documento_formatado', fn (Comprador $c) => strtoupper($c->tipo_documento) . ': ' . $c->documentoFormatado())
            ->addColumn('status_badge', fn (Comprador $c) => $c->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->addColumn('actions', fn (Comprador $c) => $c->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.compradores.form', [
            'comprador' => new Comprador(['tipo_documento' => 'cpf', 'ativo' => true]),
        ]);
    }

    public function edit(Comprador $comprador)
    {
        return view('content.admin.compradores.form', [
            'comprador' => $comprador,
        ]);
    }

    public function store(StoreCompradorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $criarLogin = (bool) ($data['criar_login'] ?? false);
        unset($data['criar_login']);

        [$comprador, $senha] = DB::transaction(function () use ($data, $criarLogin) {
            $senha = null;
            if ($criarLogin) {
                $user = $this->criarUsuarioParaComprador($data);
                $data['user_id'] = $user->id;
                $senha = $user->_senha_gerada ?? null;
            }
            return [Comprador::create($data), $senha];
        });

        return redirect()
            ->route('admin.compradores')
            ->with('status', 'Comprador cadastrado.' . ($senha ? " Senha inicial de acesso: {$senha}" : ''));
    }

    public function update(UpdateCompradorRequest $request, Comprador $comprador): RedirectResponse
    {
        $data = $request->validated();
        $criarLogin = (bool) ($data['criar_login'] ?? false);
        unset($data['criar_login']);

        $senha = null;
        DB::transaction(function () use ($comprador, &$data, $criarLogin, &$senha) {
            // Se pediu pra criar login e ainda não tem, cria user + vincula
            if ($criarLogin && ! $comprador->user_id) {
                $user = $this->criarUsuarioParaComprador($data);
                $data['user_id'] = $user->id;
                $senha = $user->_senha_gerada ?? null;
            }
            // Se já tem user vinculado, mantém sincronizado nome/e-mail
            if ($comprador->user_id) {
                $user = User::find($comprador->user_id);
                if ($user) {
                    $user->fill(['name' => $data['nome'], 'email' => $data['email'] ?: $user->email])->save();
                }
            }
            $comprador->update($data);
        });

        return redirect()
            ->route('admin.compradores')
            ->with('status', 'Comprador atualizado.' . ($senha ? " Senha inicial de acesso: {$senha}" : ''));
    }

    /**
     * Cria um usuário do sistema com role 'comprador'. A senha inicial é
     * random e volta na propriedade _senha_gerada para o admin passar ao user.
     */
    private function criarUsuarioParaComprador(array $data): User
    {
        $senha = Str::random(10);

        $user = User::create([
            'name' => $data['nome'],
            'email' => $data['email'],
            'password' => Hash::make($senha),
            'cpf_cnpj' => $data['documento'] ?? null,
            'phone' => $data['telefone'] ?? null,
            'status' => 'ativo',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('comprador');
        $user->_senha_gerada = $senha;

        return $user;
    }

    public function destroy(Comprador $comprador): JsonResponse
    {
        if ($comprador->processos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Há processos vinculados a este comprador. Desative-o ou desvincule antes.',
            ], 422);
        }

        $comprador->delete();

        return response()->json(['status' => 'success', 'message' => 'Comprador excluído.']);
    }
}
