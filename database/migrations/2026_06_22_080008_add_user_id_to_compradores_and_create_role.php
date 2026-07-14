<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Vincula opcionalmente um Comprador a um User da plataforma (para que ele
     * possa fazer login e ver apenas os processos aos quais está vinculado).
     * Também garante a existência da role 'comprador'.
     */
    public function up(): void
    {
        Schema::table('compradores', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Role::firstOrCreate(['name' => 'comprador', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Schema::table('compradores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Role::where('name', 'comprador')->where('guard_name', 'web')->delete();
    }
};
