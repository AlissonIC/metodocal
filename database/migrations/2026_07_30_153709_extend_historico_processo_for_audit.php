<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expande historico_processo para servir como log de auditoria completo do processo:
     * registra criação/edição/exclusão de qualquer entidade filha (observação, dívida,
     * comissão, negociação, documento) além das mudanças de status que já eram gravadas.
     */
    public function up(): void
    {
        Schema::table('historico_processo', function (Blueprint $table) {
            $table->string('acao', 20)->nullable()->after('user_id');       // created | updated | deleted | status_changed
            $table->string('entidade', 40)->nullable()->after('acao');      // processo | status | observacao | fatura | comissao | negociacao | documento | ...
            $table->unsignedBigInteger('entidade_id')->nullable()->after('entidade');
            $table->index(['processo_id', 'created_at'], 'historico_processo_processo_id_created_at_index');
        });

        // Marca entradas antigas como mudança de status (assim a UI nova sabe renderizar corretamente).
        DB::table('historico_processo')->whereNull('acao')->update([
            'acao' => 'status_changed',
            'entidade' => 'status',
        ]);

        // Torna status_novo opcional — logs de CRUD puro não têm status.
        Schema::table('historico_processo', function (Blueprint $table) {
            $table->string('status_novo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('historico_processo', function (Blueprint $table) {
            $table->dropIndex('historico_processo_processo_id_created_at_index');
            $table->dropColumn(['acao', 'entidade', 'entidade_id']);
            $table->string('status_novo')->nullable(false)->change();
        });
    }
};
