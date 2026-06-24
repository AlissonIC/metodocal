<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate de feature liberada por plano de assinatura.
 *
 * Lógica:
 *   - Admin → passa sempre (admin vê tudo).
 *   - Usuário com a permission (atribuída via SubscriptionObserver quando
 *     o plano ativo libera o módulo) → passa.
 *   - Caso contrário → renderiza a view de upgrade (NÃO 403).
 *
 * Uso na rota:
 *   Route::middleware('plan_feature:access.agenda.view,Agenda')->group(...)
 *
 * Segundo argumento (opcional) é o nome amigável da feature, usado na
 * view de upgrade pra dizer "A Agenda faz parte de planos..." etc.
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $permission, ?string $featureName = null): Response
    {
        $user = $request->user();

        if ($user && ($user->hasRole('admin') || $user->hasPermissionTo($permission))) {
            return $next($request);
        }

        // Requests JSON (datatables/ajax) recebem 403 simples
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Esse recurso não está disponível no seu plano.',
            ], 403);
        }

        return response()->view('content.pages.pages-upgrade-plan', [
            'featureName' => $featureName ?? 'Esse recurso',
            'permission'  => $permission,
        ], 200);
    }
}
