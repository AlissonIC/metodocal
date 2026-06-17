<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('licenciado')) {
            return redirect()->route('licenciado.dashboard');
        }
        return redirect()->route('mentorado.dashboard');
    }

    public function admin()
    {
        return view('content.dashboards.admin');
    }

    public function mentorado()
    {
        return view('content.dashboards.mentorado');
    }

    public function licenciado()
    {
        return view('content.dashboards.licenciado');
    }
}
