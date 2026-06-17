<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->decimal('valor', 12, 2);
            $table->date('vencimento');
            $table->enum('status', ['pendente', 'paga', 'atrasada', 'cancelada'])->default('pendente');
            $table->dateTime('pago_em')->nullable();
            $table->enum('metodo', ['pix', 'boleto', 'cartao', 'manual'])->nullable();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_preference_id')->nullable()->index();
            $table->text('link_pagamento')->nullable();
            $table->text('qr_code')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturas');
    }
};
