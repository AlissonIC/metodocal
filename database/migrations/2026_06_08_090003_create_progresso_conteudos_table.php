<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progresso_conteudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conteudo_id')->constrained('conteudos')->cascadeOnDelete();
            $table->dateTime('concluido_em');
            $table->timestamps();

            $table->unique(['user_id', 'conteudo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progresso_conteudos');
    }
};
