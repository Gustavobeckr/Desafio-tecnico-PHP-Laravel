<?php

namespace Database\Factories;

use App\Enums\StatusAnalise;
use App\Enums\TipoCredito;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnaliseCredito>
 */
class AnaliseCreditoFactory extends Factory
{
    private const PARCELAS = 12;

    /**
     * A análise guarda uma cópia dos dados declarados na solicitação, então o
     * cliente associado nasce com os mesmos nome/CPF/renda.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nome = fake()->name();
        $cpf = ClienteFactory::cpf();
        $renda = fake()->randomFloat(2, 5000, 20000);

        return [
            'cliente_id' => Cliente::factory()->state([
                'nome' => $nome,
                'cpf' => $cpf,
                'renda_mensal' => $renda,
            ]),
            'nome' => $nome,
            'cpf' => $cpf,
            'renda_mensal' => $renda,
            'tipo_credito' => fake()->randomElement(TipoCredito::cases()),
            'valor_solicitado' => fake()->randomFloat(2, 1000, 10000),
            'status' => StatusAnalise::PENDENTE,
            'score' => null,
            'taxa_juros' => null,
            'valor_parcela' => null,
            'motivo_rejeicao' => null,
        ];
    }

    /**
     * A parcela é derivada aqui, e não num state, porque os atributos passados
     * em make()/create() só são mesclados depois dos states — derivar antes
     * deixaria valor_parcela inconsistente com o valor_solicitado do teste.
     * Uma parcela informada explicitamente é preservada.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AnaliseCredito $analise): void {
            if ($analise->valor_parcela === null && $analise->taxa_juros !== null) {
                $analise->valor_parcela = self::parcela(
                    (float) $analise->valor_solicitado,
                    (float) $analise->taxa_juros,
                );
            }
        });
    }

    public function aprovada(float $taxaJuros = 2.9, int $score = 850): static
    {
        return $this->state(fn () => [
            'status' => StatusAnalise::APROVADO,
            'score' => $score,
            'taxa_juros' => $taxaJuros,
            'motivo_rejeicao' => null,
        ]);
    }

    public function reprovada(string $motivo = 'Score de crédito muito baixo', int $score = 150): static
    {
        return $this->state(fn () => [
            'status' => StatusAnalise::REPROVADO,
            'score' => $score,
            'taxa_juros' => null,
            'valor_parcela' => null,
            'motivo_rejeicao' => $motivo,
        ]);
    }

    public function contratada(): static
    {
        return $this->aprovada()->state(fn () => ['status' => StatusAnalise::CONTRATADO]);
    }

    public function semCliente(): static
    {
        return $this->state(fn () => ['cliente_id' => null]);
    }

    /**
     * Juros simples sobre o valor solicitado, diluídos em 12 parcelas fixas.
     */
    private static function parcela(float $valorSolicitado, float $taxaPercentual): float
    {
        $total = $valorSolicitado * (1 + $taxaPercentual / 100 * self::PARCELAS);

        return round($total / self::PARCELAS, 2);
    }
}
