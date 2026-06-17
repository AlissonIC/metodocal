<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['mentorado', 'licenciado']);
            $table->decimal('preco', 10, 2)->default(0);
            $table->enum('recorrencia', ['mensal', 'anual', 'vitalicio'])->default('mensal');
            $table->json('permissions')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
