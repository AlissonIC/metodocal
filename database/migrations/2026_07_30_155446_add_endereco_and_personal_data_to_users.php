<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expande cadastro de usuário para conter endereço + dados pessoais completos.
     * Prepara a unificação de "usuários do sistema" e "compradores" num único menu.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo_documento', 4)->nullable()->after('cpf_cnpj');   // cpf | cnpj
            $table->date('data_nascimento')->nullable()->after('tipo_documento');

            $table->string('cep', 10)->nullable()->after('data_nascimento');
            $table->string('logradouro', 160)->nullable()->after('cep');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento', 80)->nullable()->after('numero');
            $table->string('bairro', 80)->nullable()->after('complemento');
            $table->string('cidade', 80)->nullable()->after('bairro');
            $table->string('uf', 2)->nullable()->after('cidade');

            $table->text('observacoes')->nullable()->after('uf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_documento', 'data_nascimento',
                'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
                'observacoes',
            ]);
        });
    }
};
