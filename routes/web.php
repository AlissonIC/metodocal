<?php

use App\Http\Controllers\Admin\ComissaoController as AdminComissaoController;
use App\Http\Controllers\Admin\ConteudoController as AdminConteudoController;
use App\Http\Controllers\Admin\FinanceiroController;
use App\Http\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Admin\NotificacaoController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SessaoController as AdminSessaoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\EmpresaGuinchoController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProcessoLimpaNomeController;
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
// Landing pública
// ----------------------------------------------------------------------------
Route::get('/', [LandingController::class, 'index'])->name('home');

// ----------------------------------------------------------------------------
// Acesso público (apenas visitantes)
// ----------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginBasic::class, 'index'])->name('login');
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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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
        Route::get('/checkout/aguardando/{fatura}/status', [CheckoutController::class, 'status'])->name('checkout.status');
        Route::get('/checkout/sucesso', [CheckoutController::class, 'sucesso'])->name('checkout.sucesso');
        Route::get('/checkout/falha', [CheckoutController::class, 'falha'])->name('checkout.falha');
        Route::get('/checkout/pendente', [CheckoutController::class, 'pendente'])->name('checkout.pendente');
    });

    // Faturas (cliente)
    Route::middleware('permission:access.faturas.view')->group(function () {
        Route::get('/faturas', [FaturasController::class, 'index'])->name('faturas.index');
        Route::get('/faturas/{fatura}', [FaturasController::class, 'show'])->name('faturas.show');
    });

    // Guincho (cliente + admin servidos pela mesma URL)
    Route::middleware('permission:access.empresas-guincho.view')->group(function () {
        Route::get('/guincho', [EmpresaGuinchoController::class, 'index'])->name('guincho.index');
        Route::get('/guincho/datatable', [EmpresaGuinchoController::class, 'datatable'])->name('guincho.datatable');
    });

    // Guincho — ações admin (mesmo prefixo, permissão diferente)
    Route::middleware('permission:access.empresas-guincho.manage')->group(function () {
        Route::get('/guincho/novo', [EmpresaGuinchoController::class, 'create'])->name('guincho.create');
        Route::post('/guincho', [EmpresaGuinchoController::class, 'store'])->name('guincho.store');
        Route::get('/guincho/{empresaGuincho}/editar', [EmpresaGuinchoController::class, 'edit'])->name('guincho.edit');
        Route::patch('/guincho/{empresaGuincho}', [EmpresaGuinchoController::class, 'update'])->name('guincho.update');
        Route::delete('/guincho/{empresaGuincho}', [EmpresaGuinchoController::class, 'destroy'])->name('guincho.destroy');
    });

    // Limpa Nome (cliente + admin servidos pela mesma URL)
    Route::middleware('permission:access.limpa-nome.view')->group(function () {
        Route::get('/limpa-nome', [ProcessoLimpaNomeController::class, 'index'])->name('limpa-nome.index');
        Route::get('/limpa-nome/datatable', [ProcessoLimpaNomeController::class, 'datatable'])->name('limpa-nome.datatable');
        Route::get('/limpa-nome/novo', [ProcessoLimpaNomeController::class, 'create'])->name('limpa-nome.create');
        Route::post('/limpa-nome', [ProcessoLimpaNomeController::class, 'store'])->name('limpa-nome.store');
        Route::get('/limpa-nome/{processo}', [ProcessoLimpaNomeController::class, 'show'])->name('limpa-nome.show');
        Route::get('/limpa-nome/{processo}/editar', [ProcessoLimpaNomeController::class, 'edit'])->name('limpa-nome.edit');
        Route::patch('/limpa-nome/{processo}', [ProcessoLimpaNomeController::class, 'update'])->name('limpa-nome.update');
        Route::delete('/limpa-nome/{processo}', [ProcessoLimpaNomeController::class, 'destroy'])->name('limpa-nome.destroy');
        Route::post('/limpa-nome/{processo}/documentos', [ProcessoLimpaNomeController::class, 'uploadDocumento'])->name('limpa-nome.documentos.store');
        Route::delete('/limpa-nome/documentos/{documento}', [ProcessoLimpaNomeController::class, 'destroyDocumento'])->name('limpa-nome.documentos.destroy');
        Route::get('/limpa-nome/documentos/{documento}/download', [ProcessoLimpaNomeController::class, 'downloadDocumento'])->name('limpa-nome.documentos.download');
    });

    // Limpa Nome — ações admin (mesmo prefixo, permissão diferente)
    Route::middleware('permission:access.limpa-nome.manage')->group(function () {
        Route::patch('/limpa-nome/{processo}/status', [ProcessoLimpaNomeController::class, 'updateStatus'])->name('limpa-nome.status');
        Route::patch('/limpa-nome/{processo}/observacoes', [ProcessoLimpaNomeController::class, 'updateObservacoes'])->name('limpa-nome.observacoes');
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
        Route::get('/crm/novo', [LicClienteController::class, 'create'])->name('licenciado.crm.create');
        Route::post('/crm', [LicClienteController::class, 'store'])->name('licenciado.crm.store');
        Route::get('/crm/{cliente}/editar', [LicClienteController::class, 'edit'])->name('licenciado.crm.edit');
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
        Route::get('/usuarios/novo', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
    Route::middleware('permission:access.plans.view')->group(function () {
        Route::get('/planos', [PlanController::class, 'index'])->name('admin.plans');
        Route::get('/planos/datatable', [PlanController::class, 'datatable']);
        Route::get('/planos/novo', [PlanController::class, 'create'])->name('admin.plans.create');
        Route::post('/planos', [PlanController::class, 'store'])->name('admin.plans.store');
        Route::get('/planos/{plan}/editar', [PlanController::class, 'edit'])->name('admin.plans.edit');
        Route::patch('/planos/{plan}', [PlanController::class, 'update'])->name('admin.plans.update');
        Route::delete('/planos/{plan}', [PlanController::class, 'destroy'])->name('admin.plans.destroy');
    });
    Route::middleware('permission:access.sessoes.manage')->group(function () {
        Route::get('/sessoes', [AdminSessaoController::class, 'index'])->name('admin.sessoes');
        Route::get('/sessoes/datatable', [AdminSessaoController::class, 'datatable']);
        Route::get('/sessoes/novo', [AdminSessaoController::class, 'create'])->name('admin.sessoes.create');
        Route::post('/sessoes', [AdminSessaoController::class, 'store'])->name('admin.sessoes.store');
        Route::get('/sessoes/{sessao}/editar', [AdminSessaoController::class, 'edit'])->name('admin.sessoes.edit');
        Route::patch('/sessoes/{sessao}', [AdminSessaoController::class, 'update'])->name('admin.sessoes.update');
        Route::delete('/sessoes/{sessao}', [AdminSessaoController::class, 'destroy'])->name('admin.sessoes.destroy');
    });
    Route::middleware('permission:access.conteudos.manage')->group(function () {
        Route::get('/conteudos-admin', [AdminConteudoController::class, 'index'])->name('admin.conteudos');
        Route::get('/conteudos-admin/datatable', [AdminConteudoController::class, 'datatable']);
        Route::get('/conteudos-admin/novo', [AdminConteudoController::class, 'create'])->name('admin.conteudos.create');
        Route::post('/conteudos-admin', [AdminConteudoController::class, 'store'])->name('admin.conteudos.store');
        Route::get('/conteudos-admin/{conteudo}/editar', [AdminConteudoController::class, 'edit'])->name('admin.conteudos.edit');
        Route::patch('/conteudos-admin/{conteudo}', [AdminConteudoController::class, 'update'])->name('admin.conteudos.update');
        Route::delete('/conteudos-admin/{conteudo}', [AdminConteudoController::class, 'destroy'])->name('admin.conteudos.destroy');
    });
    Route::middleware('permission:access.materiais.manage')->group(function () {
        Route::get('/materiais-admin', [AdminMaterialController::class, 'index'])->name('admin.materiais');
        Route::get('/materiais-admin/datatable', [AdminMaterialController::class, 'datatable']);
        Route::get('/materiais-admin/novo', [AdminMaterialController::class, 'create'])->name('admin.materiais.create');
        Route::post('/materiais-admin', [AdminMaterialController::class, 'store'])->name('admin.materiais.store');
        Route::get('/materiais-admin/{material}/editar', [AdminMaterialController::class, 'edit'])->name('admin.materiais.edit');
        Route::patch('/materiais-admin/{material}', [AdminMaterialController::class, 'update'])->name('admin.materiais.update');
        Route::delete('/materiais-admin/{material}', [AdminMaterialController::class, 'destroy'])->name('admin.materiais.destroy');
    });
    Route::middleware('permission:access.comissoes.manage')->group(function () {
        Route::get('/comissoes-admin', [AdminComissaoController::class, 'index'])->name('admin.comissoes');
        Route::get('/comissoes-admin/datatable', [AdminComissaoController::class, 'datatable']);
        Route::get('/comissoes-admin/novo', [AdminComissaoController::class, 'create'])->name('admin.comissoes.create');
        Route::post('/comissoes-admin', [AdminComissaoController::class, 'store'])->name('admin.comissoes.store');
        Route::get('/comissoes-admin/{comissao}/editar', [AdminComissaoController::class, 'edit'])->name('admin.comissoes.edit');
        Route::patch('/comissoes-admin/{comissao}', [AdminComissaoController::class, 'update'])->name('admin.comissoes.update');
        Route::delete('/comissoes-admin/{comissao}', [AdminComissaoController::class, 'destroy'])->name('admin.comissoes.destroy');
    });
});
