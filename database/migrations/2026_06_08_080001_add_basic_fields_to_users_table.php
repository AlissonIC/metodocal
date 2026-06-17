<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('cpf_cnpj', 20)->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('cpf_cnpj');
            $table->enum('status', ['ativo', 'inativo', 'bloqueado'])->default('ativo')->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'cpf_cnpj', 'avatar', 'status', 'last_login_at']);
        });
    }
};
