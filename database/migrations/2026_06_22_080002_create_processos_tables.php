<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->decimal('valor_padrao', 12, 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('processos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->string('nome_completo');
            $table->enum('tipo_documento', ['cpf', 'cnpj']);
            $table->string('documento', 20);
            $table->string('email_contato')->nullable();
            $table->string('telefone_contato', 40)->nullable();
            $table->enum('status', [
                'cadastrado',
                'em_analise',
                'consulta_valor',
                'liminar_protocolada',
                'aguardando_prazo_45d',
                'concluido',
                'cancelado',
            ])->default('cadastrado');
            $table->date('data_protocolo_liminar')->nullable();
            $table->date('data_previsao_conclusao')->nullable();
            $table->date('data_conclusao')->nullable();
            $table->text('observacoes_cliente')->nullable();
            $table->text('observacoes_admin')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'servico_id']);
            $table->index('documento');
        });

        Schema::create('dividas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->string('credor');
            $table->decimal('valor', 12, 2)->default(0);
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });

        Schema::create('documentos_processo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('categoria')->nullable();
            $table->string('nome_original');
            $table->string('arquivo');
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->string('mime', 120)->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });

        Schema::create('historico_processo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_anterior')->nullable();
            $table->string('status_novo');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_processo');
        Schema::dropIfExists('documentos_processo');
        Schema::dropIfExists('dividas');
        Schema::dropIfExists('processos');
        Schema::dropIfExists('servicos');
    }
};
