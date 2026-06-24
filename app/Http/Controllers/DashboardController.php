<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return view('content.dashboards.admin');
        }
        if ($user->hasRole('licenciado')) {
            return view('content.dashboards.licenciado');
        }
        return view('content.dashboards.mentorado');
    }
}
