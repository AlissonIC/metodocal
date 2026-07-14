<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Endereço da pessoa do processo — direto na própria tabela.
        Schema::table('processos', function (Blueprint $table) {
            $table->string('cep', 10)->nullable()->after('telefone_contato');
            $table->string('logradouro', 160)->nullable()->after('cep');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento', 80)->nullable()->after('numero');
            $table->string('bairro', 80)->nullable()->after('complemento');
            $table->string('cidade', 80)->nullable()->after('bairro');
            $table->string('uf', 2)->nullable()->after('cidade');
        });

        // Veículo vinculado ao processo (1:1).
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')
                ->unique()
                ->constrained('processos')
                ->cascadeOnDelete();
            $table->string('placa', 10)->nullable()->index();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->smallInteger('ano_fabricacao')->nullable();
            $table->smallInteger('ano_modelo')->nullable();
            $table->string('cor', 30)->nullable();
            $table->string('chassi', 20)->nullable();
            $table->string('renavam', 20)->nullable();
            $table->enum('combustivel', ['gasolina', 'alcool', 'flex', 'diesel', 'eletrico', 'hibrido', 'gnv'])->nullable();
            $table->unsignedInteger('quilometragem')->nullable();
            $table->decimal('valor_fipe', 12, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');

        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn(['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf']);
        });
    }
};
