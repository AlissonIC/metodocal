<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate de feature liberada por plano de assinatura.
 *
 * Lógica:
 *   - Usuário com a permission (atribuída via SubscriptionObserver quando
 *     o plano ativo libera o módulo) → passa.
 *   - Admin SEM a permission → passa NÃO. Essas são telas de cliente
 *     (agenda, conteúdos, etc), não fazem sentido pro admin. Admin verá
 *     a view de upgrade com mensagem específica explicando isso.
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

        if ($user) {
            try {
                if ($user->hasPermissionTo($permission)) {
                    return $next($request);
                }
            } catch (\Throwable $e) {
                // permission ainda não seedada — trata como "não tem"
            }
        }

        // Requests JSON (datatables/ajax) recebem 403 simples
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Esse recurso não está disponível.',
            ], 403);
        }

        return response()->view('content.pages.pages-upgrade-plan', [
            'featureName' => $featureName ?? 'Esse recurso',
            'permission'  => $permission,
        ], 200);
    }
}
