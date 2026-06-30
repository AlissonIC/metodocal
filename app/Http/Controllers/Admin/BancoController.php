<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBancoRequest;
use App\Http\Requests\Admin\UpdateBancoRequest;
use App\Models\Banco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class BancoController extends Controller
{
    public function index()
    {
        return view('content.admin.bancos.index', [
            'bancos' => Banco::orderBy('nome')->get(),
        ]);
    }

    public function store(StoreBancoRequest $request): RedirectResponse
    {
        Banco::create($request->validated());

        return redirect()
            ->route('admin.bancos')
            ->with('status', 'Banco cadastrado com sucesso.');
    }

    public function update(UpdateBancoRequest $request, Banco $banco): RedirectResponse
    {
        $banco->update($request->validated());

        return redirect()
            ->route('admin.bancos')
            ->with('status', 'Banco atualizado.');
    }

    public function destroy(Banco $banco): JsonResponse
    {
        $banco->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Banco excluído.',
        ]);
    }
}
