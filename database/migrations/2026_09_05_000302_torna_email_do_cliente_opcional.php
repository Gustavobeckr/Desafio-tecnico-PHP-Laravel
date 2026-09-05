<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A solicitação de análise cadastra o cliente a partir do CPF, e o formulário
 * não coleta e-mail. Manter a coluna obrigatória exigiria inventar um endereço.
 * O `unique` continua valendo — vários NULL convivem num índice único.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
