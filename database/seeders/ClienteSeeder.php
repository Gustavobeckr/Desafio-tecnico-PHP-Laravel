<?php

namespace Database\Seeders;

use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Database\Seeder;

/**
 * Base de demonstração. Os CPFs terminam nos dígitos que o mock do Bureau
 * reconhece, para que novas análises pela tela caiam em cenários variados.
 */
class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = Cliente::factory()->comUltimoDigito(3)->comRenda(9000)->create([
            'nome' => 'Maria Oliveira',
            'email' => 'maria.oliveira@example.com',
        ]);

        AnaliseCredito::factory()->aprovada()->create([
            'cliente_id' => $cliente->id,
            'nome' => $cliente->nome,
            'cpf' => $cliente->cpf,
            'renda_mensal' => $cliente->renda_mensal,
            'valor_solicitado' => 10000,
        ]);

        AnaliseCredito::factory()->reprovada()->create([
            'cliente_id' => $cliente->id,
            'nome' => $cliente->nome,
            'cpf' => $cliente->cpf,
            'renda_mensal' => $cliente->renda_mensal,
            'valor_solicitado' => 80000,
            'motivo_rejeicao' => 'Comprometimento de renda superior a 30%',
        ]);

        foreach ([1, 2, 3, 4, 5, 6, 0] as $digito) {
            Cliente::factory()->comUltimoDigito($digito)->create();
        }

        Cliente::factory()->count(12)->create();
    }
}
