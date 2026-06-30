<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compradores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 160);
            $table->enum('tipo_documento', ['cpf', 'cnpj'])->default('cpf');
            $table->string('documento', 20)->unique();
            $table->string('email')->nullable();
            $table->string('telefone', 40)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('ativo');
        });

        Schema::table('processos', function (Blueprint $table) {
            $table->foreignId('comprador_id')
                ->nullable()
                ->after('servico_id')
                ->constrained('compradores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropForeign(['comprador_id']);
            $table->dropColumn('comprador_id');
        });
        Schema::dropIfExists('compradores');
    }
};
