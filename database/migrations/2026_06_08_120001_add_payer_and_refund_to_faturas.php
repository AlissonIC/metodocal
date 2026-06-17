<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faturas', function (Blueprint $table) {
            $table->string('payer_name')->nullable()->after('metodo');
            $table->string('payer_email')->nullable()->after('payer_name');
            $table->string('payer_document', 30)->nullable()->after('payer_email');
            $table->json('payer_address')->nullable()->after('payer_document');
            $table->json('payer_info')->nullable()->after('payer_address');
            $table->string('gateway_refund_id')->nullable()->after('gateway_payment_id');
            $table->dateTime('estornada_em')->nullable()->after('pago_em');
        });

        // MySQL: enum tem que ser recriado para aceitar novo valor 'estornada'
        DB::statement("ALTER TABLE faturas MODIFY COLUMN status ENUM('pendente','paga','atrasada','cancelada','estornada') DEFAULT 'pendente'");

        Schema::table('payment_events', function (Blueprint $table) {
            $table->foreignId('fatura_id')->nullable()->after('subscription_id')->constrained('faturas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->dropForeign(['fatura_id']);
            $table->dropColumn('fatura_id');
        });

        DB::statement("ALTER TABLE faturas MODIFY COLUMN status ENUM('pendente','paga','atrasada','cancelada') DEFAULT 'pendente'");

        Schema::table('faturas', function (Blueprint $table) {
            $table->dropColumn([
                'payer_name', 'payer_email', 'payer_document', 'payer_address',
                'payer_info', 'gateway_refund_id', 'estornada_em',
            ]);
        });
    }
};
