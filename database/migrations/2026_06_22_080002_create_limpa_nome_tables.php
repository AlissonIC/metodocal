<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processos_limpa_nome', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome_completo');
            $table->enum('tipo_documento', ['cpf', 'cnpj']);
            $table->string('documento', 20);
            $table->string('email_contato')->nullable();
            $table->string('telefone_contato', 40)->nullable();
            $table->enum('tipo', ['limpa_nome', 'aquisicao', 'negociacao_divida']);
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
            $table->index(['status', 'tipo']);
            $table->index('documento');
        });

        Schema::create('dividas_limpa_nome', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos_limpa_nome')->cascadeOnDelete();
            $table->string('credor');
            $table->decimal('valor', 12, 2)->default(0);
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });

        Schema::create('documentos_limpa_nome', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos_limpa_nome')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('categoria')->nullable();
            $table->string('nome_original');
            $table->string('arquivo');
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->string('mime', 120)->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });

        Schema::create('historico_limpa_nome', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos_limpa_nome')->cascadeOnDelete();
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
        Schema::dropIfExists('historico_limpa_nome');
        Schema::dropIfExists('documentos_limpa_nome');
        Schema::dropIfExists('dividas_limpa_nome');
        Schema::dropIfExists('processos_limpa_nome');
    }
};
