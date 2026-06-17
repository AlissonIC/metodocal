<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_licenciado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licensed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('cpf_cnpj', 20)->nullable();
            $table->text('endereco')->nullable();
            $table->enum('status', ['lead', 'ativo', 'perdido'])->default('lead');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['licensed_by_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_licenciado');
    }
};
