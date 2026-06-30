<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite faturas avulsas vinculadas a um Processo (não a uma assinatura).
     * subscription_id/plan_id viram nullable; processo_id é a nova FK opcional.
     */
    public function up(): void
    {
        // Drop FKs antes de mudar nullability
        Schema::table('faturas', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['plan_id']);
        });

        Schema::table('faturas', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->change();
            $table->foreignId('plan_id')->nullable()->change();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();

            $table->foreignId('processo_id')->nullable()->after('plan_id')
                ->constrained('processos')->cascadeOnDelete();
            $table->string('descricao')->nullable()->after('processo_id');

            $table->index(['processo_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('faturas', function (Blueprint $table) {
            $table->dropIndex(['processo_id', 'status']);
            $table->dropForeign(['processo_id']);
            $table->dropColumn(['processo_id', 'descricao']);

            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['plan_id']);
        });

        Schema::table('faturas', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable(false)->change();
            $table->foreignId('plan_id')->nullable(false)->change();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });
    }
};
