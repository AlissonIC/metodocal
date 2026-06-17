<?php

use App\Http\Controllers\Admin\ComissaoController as AdminComissaoController;
use App\Http\Controllers\Admin\ConteudoController as AdminConteudoController;
use App\Http\Controllers\Admin\FinanceiroController;
use App\Http\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Admin\NotificacaoController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SessaoController as AdminSessaoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ResetPasswordBasic;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaturasController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\Licenciado\ClienteController as LicClienteController;
use App\Http\Controllers\Licenciado\ComissaoController as LicComissaoController;
use App\Http\Controllers\Licenciado\MaterialController as LicMaterialController;
use App\Http\Controllers\Mentorado\AgendaController;
use App\Http\Controllers\Mentorado\ConteudoController as MentConteudoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// ----------------------------------------------------------------------------
// Locale
// ----------------------------------------------------------------------------
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// ----------------------------------------------------------------------------
// Acesso público (apenas visitantes)
// ----------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginBasic::class, 'index'])->name('login');
    Route::get('/entrar', [LoginBasic::class, 'index'])->name('login.show');
    Route::post('/entrar', [LoginBasic::class, 'authenticate'])->name('login.attempt');

    Route::get('/registrar', [RegisterBasic::class, 'index'])->name('register');
    Route::post('/registrar', [RegisterBasic::class, 'store'])->name('register.store');

    Route::get('/esqueci-senha', [ForgotPasswordBasic::class, 'index'])->name('password.request');
    Route::post('/esqueci-senha', [ForgotPasswordBasic::class, 'sendLink'])->name('password.email');

    Route::get('/redefinir-senha/{token}', [ResetPasswordBasic::class, 'index'])->name('password.reset');
    Route::post('/redefinir-senha', [ResetPasswordBasic::class, 'reset'])->name('password.update');
});

Route::post('/sair', [LoginBasic::class, 'logout'])->middleware('auth')->name('logout');

// ----------------------------------------------------------------------------
// Webhook público (sem auth, sem CSRF — excluído em bootstrap/app.php)
// ----------------------------------------------------------------------------
Route::post('/webhooks/mercadopago', [WebhookController::class, 'mercadopago'])->name('webhook.mercadopago');

// ----------------------------------------------------------------------------
// Painel (autenticado)
// ----------------------------------------------------------------------------
Route::prefix('painel')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::get('/admin', [DashboardController::class, 'admin'])
        ->middleware('permission:access.dashboard.admin')->name('admin.dashboard');
    Route::get('/mentorado', [DashboardController::class, 'mentorado'])
        ->middleware('permission:access.dashboard.mentorado')->name('mentorado.dashboard');
    Route::get('/licenciado', [DashboardController::class, 'licenciado'])
        ->middleware('permission:access.dashboard.licenciado')->name('licenciado.dashboard');

    // Perfil
    Route::middleware('permission:access.profile.edit')->group(function () {
        Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/perfil/senha', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/perfil/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
        Route::delete('/perfil/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    });

    // Minha Assinatura
    Route::get('/minha-assinatura', [SubscriptionController::class, 'show'])
        ->middleware('permission:access.minhaassinatura.view')->name('subscription.view');

    // Checkout
    Route::middleware('permission:access.minhaassinatura.view')->group(function () {
        Route::post('/contratar/{plan}', [CheckoutController::class, 'contratar'])->name('checkout.contratar');
        Route::get('/checkout/aguardando/{fatura}', [CheckoutController::class, 'aguardando'])->name('checkout.aguardando');
        Route::get('/checkout/sucesso', [CheckoutController::class, 'sucesso'])->name('checkout.sucesso');
        Route::get('/checkout/falha', [CheckoutController::class, 'falha'])->name('checkout.falha');
        Route::get('/checkout/pendente', [CheckoutController::class, 'pendente'])->name('checkout.pendente');
    });

    // Faturas (cliente)
    Route::middleware('permission:access.faturas.view')->group(function () {
        Route::get('/faturas', [FaturasController::class, 'index'])->name('faturas.index');
        Route::get('/faturas/{fatura}', [FaturasController::class, 'show'])->name('faturas.show');
    });

    // Admin - Financeiro
    Route::middleware('permission:access.financeiro.manage')->group(function () {
        Route::get('/financeiro', [FinanceiroController::class, 'index'])->name('admin.financeiro');
        Route::get('/financeiro/datatable', [FinanceiroController::class, 'datatable'])->name('admin.financeiro.datatable');
        Route::get('/financeiro/eventos-pagamento/datatable', [FinanceiroController::class, 'paymentEvents']);
        Route::get('/financeiro/{fatura}', [FinanceiroController::class, 'show'])->name('admin.financeiro.show');
        Route::get('/financeiro/{fatura}/refresh', [FinanceiroController::class, 'refresh'])->name('admin.financeiro.refresh');
        Route::patch('/financeiro/{fatura}/marcar-paga', [FinanceiroController::class, 'marcarPaga'])->name('admin.financeiro.marcar-paga');
        Route::patch('/financeiro/{fatura}/cancelar', [FinanceiroController::class, 'cancelar'])->name('admin.financeiro.cancelar');
        Route::patch('/financeiro/{fatura}/estornar', [FinanceiroController::class, 'estornar'])->name('admin.financeiro.estornar');
        Route::patch('/financeiro/{fatura}/status', [FinanceiroController::class, 'mudarStatus'])->name('admin.financeiro.status');
    });

    // Admin - Notificações
    Route::middleware('permission:access.notificacoes.manage')->group(function () {
        Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('admin.notificacoes');
        Route::get('/notificacoes/datatable', [NotificacaoController::class, 'datatable'])->name('admin.notificacoes.datatable');
        Route::get('/notificacoes/{notificacao}', [NotificacaoController::class, 'show'])->name('admin.notificacoes.show');
        Route::get('/notificacoes/{notificacao}/preview', [NotificacaoController::class, 'preview'])->name('admin.notificacoes.preview');
        Route::patch('/notificacoes/{notificacao}/resend', [NotificacaoController::class, 'resend'])->name('admin.notificacoes.resend');
        Route::patch('/notificacoes/{notificacao}/cancel', [NotificacaoController::class, 'cancel'])->name('admin.notificacoes.cancel');
    });

    // ---------- Mentorado ----------
    Route::middleware('permission:access.agenda.view')->group(function () {
        Route::get('/agenda', [AgendaController::class, 'index'])->name('mentorado.agenda');
        Route::get('/agenda/events', [AgendaController::class, 'events'])->name('mentorado.agenda.events');
        Route::post('/agenda/{sessao}/complete', [AgendaController::class, 'complete'])->name('mentorado.agenda.complete');
        Route::post('/agenda/{sessao}/cancel', [AgendaController::class, 'cancel'])->name('mentorado.agenda.cancel');
    });
    Route::middleware('permission:access.conteudos.view')->group(function () {
        Route::get('/conteudos', [MentConteudoController::class, 'index'])->name('mentorado.conteudos');
        Route::post('/conteudos/{conteudo}/toggle', [MentConteudoController::class, 'toggleComplete'])->name('mentorado.conteudos.toggle');
    });

    // ---------- Licenciado ----------
    Route::middleware('permission:access.crm.view')->group(function () {
        Route::get('/crm', [LicClienteController::class, 'index'])->name('licenciado.crm');
        Route::get('/crm/datatable', [LicClienteController::class, 'datatable'])->name('licenciado.crm.datatable');
        Route::get('/crm/{cliente}', [LicClienteController::class, 'show'])->name('licenciado.crm.show');
        Route::post('/crm', [LicClienteController::class, 'store'])->name('licenciado.crm.store');
        Route::patch('/crm/{cliente}', [LicClienteController::class, 'update'])->name('licenciado.crm.update');
        Route::delete('/crm/{cliente}', [LicClienteController::class, 'destroy'])->name('licenciado.crm.destroy');
    });
    Route::middleware('permission:access.materiais.view')->group(function () {
        Route::get('/materiais', [LicMaterialController::class, 'index'])->name('licenciado.materiais');
        Route::get('/materiais/{material}/download', [LicMaterialController::class, 'download'])->name('licenciado.materiais.download');
    });
    Route::middleware('permission:access.comissoes.view')->group(function () {
        Route::get('/comissoes', [LicComissaoController::class, 'index'])->name('licenciado.comissoes');
        Route::get('/comissoes/datatable', [LicComissaoController::class, 'datatable'])->name('licenciado.comissoes.datatable');
    });

    // ---------- Admin ----------
    Route::middleware('permission:access.users.view')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('admin.users');
        Route::get('/usuarios/datatable', [UserController::class, 'datatable']);
        Route::get('/usuarios/{user}', [UserController::class, 'show']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::patch('/usuarios/{user}', [UserController::class, 'update']);
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);
    });
    Route::middleware('permission:access.plans.view')->group(function () {
        Route::get('/planos', [PlanController::class, 'index'])->name('admin.plans');
        Route::get('/planos/datatable', [PlanController::class, 'datatable']);
        Route::get('/planos/{plan}', [PlanController::class, 'show']);
        Route::post('/planos', [PlanController::class, 'store']);
        Route::patch('/planos/{plan}', [PlanController::class, 'update']);
        Route::delete('/planos/{plan}', [PlanController::class, 'destroy']);
    });
    Route::middleware('permission:access.sessoes.manage')->group(function () {
        Route::get('/sessoes', [AdminSessaoController::class, 'index'])->name('admin.sessoes');
        Route::get('/sessoes/datatable', [AdminSessaoController::class, 'datatable']);
        Route::get('/sessoes/{sessao}', [AdminSessaoController::class, 'show']);
        Route::post('/sessoes', [AdminSessaoController::class, 'store']);
        Route::patch('/sessoes/{sessao}', [AdminSessaoController::class, 'update']);
        Route::delete('/sessoes/{sessao}', [AdminSessaoController::class, 'destroy']);
    });
    Route::middleware('permission:access.conteudos.manage')->group(function () {
        Route::get('/conteudos-admin', [AdminConteudoController::class, 'index'])->name('admin.conteudos');
        Route::get('/conteudos-admin/datatable', [AdminConteudoController::class, 'datatable']);
        Route::get('/conteudos-admin/{conteudo}', [AdminConteudoController::class, 'show']);
        Route::post('/conteudos-admin', [AdminConteudoController::class, 'store']);
        Route::patch('/conteudos-admin/{conteudo}', [AdminConteudoController::class, 'update']);
        Route::delete('/conteudos-admin/{conteudo}', [AdminConteudoController::class, 'destroy']);
    });
    Route::middleware('permission:access.materiais.manage')->group(function () {
        Route::get('/materiais-admin', [AdminMaterialController::class, 'index'])->name('admin.materiais');
        Route::get('/materiais-admin/datatable', [AdminMaterialController::class, 'datatable']);
        Route::get('/materiais-admin/{material}', [AdminMaterialController::class, 'show']);
        Route::post('/materiais-admin', [AdminMaterialController::class, 'store']);
        Route::patch('/materiais-admin/{material}', [AdminMaterialController::class, 'update']);
        Route::delete('/materiais-admin/{material}', [AdminMaterialController::class, 'destroy']);
    });
    Route::middleware('permission:access.comissoes.manage')->group(function () {
        Route::get('/comissoes-admin', [AdminComissaoController::class, 'index'])->name('admin.comissoes');
        Route::get('/comissoes-admin/datatable', [AdminComissaoController::class, 'datatable']);
        Route::get('/comissoes-admin/{comissao}', [AdminComissaoController::class, 'show']);
        Route::post('/comissoes-admin', [AdminComissaoController::class, 'store']);
        Route::patch('/comissoes-admin/{comissao}', [AdminComissaoController::class, 'update']);
        Route::delete('/comissoes-admin/{comissao}', [AdminComissaoController::class, 'destroy']);
    });
});
