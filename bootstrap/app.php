<?php

use App\Http\Middleware\LocaleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Não autorizado.'], 403);
            }
            return response()->view('content.pages.pages-misc-not-authorized', [], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Recurso não encontrado.'], 404);
            }
            return response()->view('content.pages.pages-misc-error', [], 404);
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => 'Não autorizado.'], 403);
                }
                return response()->view('content.pages.pages-misc-not-authorized', [], 403);
            }
            return null;
        });

        // Catch-all: qualquer outra exception em produção vira a página de erro
        // amigável. Em debug (APP_DEBUG=true), deixa o Laravel mostrar o Whoops
        // pra o dev ver o stack trace.
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Não interceptar exceptions que o Laravel já trata de forma
            // específica e não-visual (redirects, validation, auth)
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof TokenMismatchException) {
                return null;
            }

            if (config('app.debug')) {
                return null;
            }

            $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Ocorreu um erro inesperado.',
                ], $status);
            }

            return response()->view('content.pages.pages-misc-error', [
                'statusCode'  => $status,
                'pageTitle'   => $status === 500 ? 'Algo deu errado' : 'Página não encontrada',
                'pageMessage' => $status === 500
                    ? 'Tivemos um problema ao processar sua solicitação. Tente novamente em instantes.'
                    : 'A página que você procura não existe ou foi movida.',
            ], $status);
        });
    })->create();
