<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Cada nível tem a sua porta de entrada. A decisão é explícita, sem cair num
     * dashboard padrão: um usuário sem role (ou com role nova) não deve herdar
     * a tela de outro perfil por acidente.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return view('content.dashboards.admin');
        }

        if ($user->hasRole('licenciado')) {
            return view('content.dashboards.licenciado');
        }

        if ($user->hasRole('mentorado')) {
            return view('content.dashboards.mentorado');
        }

        // Cliente e comprador acompanham processos de fora: não têm painel de
        // métricas, entram direto na lista do que lhes diz respeito.
        if ($user->hasRole('cliente') || $user->hasRole('comprador')) {
            return redirect()->route('processos.index');
        }

        // Sem nível reconhecido: só o perfil, para não expor dado de outro papel.
        return redirect()->route('profile.edit')
            ->with('status', 'Seu acesso ainda não tem um nível definido. Fale com o administrador.');
    }
}
