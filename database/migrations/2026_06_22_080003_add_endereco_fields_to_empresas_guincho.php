<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas_guincho', function (Blueprint $table) {
            $table->string('cnpj', 20)->nullable()->after('nome');
            $table->string('cep', 10)->nullable()->after('endereco');
            $table->string('numero', 20)->nullable()->after('cep');
            $table->string('complemento', 80)->nullable()->after('numero');
            $table->string('bairro', 100)->nullable()->after('complemento');
            $table->string('cidade', 120)->nullable()->after('bairro');
        });
    }

    public function down(): void
    {
        Schema::table('empresas_guincho', function (Blueprint $table) {
            $table->dropColumn(['cnpj', 'cep', 'numero', 'complemento', 'bairro', 'cidade']);
        });
    }
};
