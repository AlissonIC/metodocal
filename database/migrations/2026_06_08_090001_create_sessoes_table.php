<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duracao_minutos')->default(60);
            $table->string('link_reuniao')->nullable();
            $table->enum('status', ['agendada', 'concluida', 'cancelada'])->default('agendada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes');
    }
};
