<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas_guincho', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('logo')->nullable();
            $table->string('telefone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('site')->nullable();
            $table->string('estado', 2)->nullable();
            $table->json('cidades_atendidas')->nullable();
            $table->string('endereco')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'estado']);
            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas_guincho');
    }
};
