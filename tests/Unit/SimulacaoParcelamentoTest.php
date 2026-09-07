<?php

namespace Tests\Unit;

use App\Services\Credito\SimulacaoParcelamento;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SimulacaoParcelamentoTest extends TestCase
{
    public function test_reproduz_o_exemplo_do_enunciado(): void
    {
        $simulacao = SimulacaoParcelamento::calcular(10000.00, 2.9);

        $this->assertSame(3480.00, $simulacao->jurosTotais);
        $this->assertSame(13480.00, $simulacao->valorTotal);
        $this->assertSame(1123.33, $simulacao->valorParcela);
    }

    #[DataProvider('cenarios')]
    public function test_calcula_a_parcela_com_juros_simples_em_12_vezes(
        float $valorSolicitado,
        float $taxaJuros,
        float $parcelaEsperada,
    ): void {
        $this->assertSame(
            $parcelaEsperada,
            SimulacaoParcelamento::calcular($valorSolicitado, $taxaJuros)->valorParcela,
        );
    }

    /**
     * @return array<string, array{float, float, float}>
     */
    public static function cenarios(): array
    {
        return [
            'taxa reduzida' => [5000.00, 2.9, 561.67],
            'taxa padrão' => [5000.00, 4.5, 641.67],
            'valor baixo' => [1200.00, 4.5, 154.00],
        ];
    }

    public function test_expoe_o_numero_de_parcelas_como_constante(): void
    {
        $this->assertSame(12, SimulacaoParcelamento::PARCELAS);
    }
}
