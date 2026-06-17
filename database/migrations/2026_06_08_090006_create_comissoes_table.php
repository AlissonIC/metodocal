<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licensed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes_licenciado')->nullOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->date('data_referencia');
            $table->enum('status', ['pendente', 'paga', 'cancelada'])->default('pendente');
            $table->dateTime('pago_em')->nullable();
            $table->timestamps();

            $table->index(['licensed_by_user_id', 'status']);
            $table->index('data_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissoes');
    }
};
