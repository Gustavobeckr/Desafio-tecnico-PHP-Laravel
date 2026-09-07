<?php

namespace Tests\Unit;

use App\Enums\StatusAnalise;
use App\Services\Credito\PoliticaCredito;
use PHPUnit\Framework\TestCase;

class PoliticaCreditoTest extends TestCase
{
    private PoliticaCredito $politica;

    protected function setUp(): void
    {
        parent::setUp();

        $this->politica = new PoliticaCredito;
    }

    public function test_aprova_com_score_alto_aplicando_a_taxa_reduzida(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 8000, valorSolicitado: 10000, score: 850);

        $this->assertTrue($resultado->foiAprovada());
        $this->assertSame(StatusAnalise::APROVADO, $resultado->status);
        $this->assertSame(2.9, $resultado->simulacao->taxaJuros);
        $this->assertSame(1123.33, $resultado->simulacao->valorParcela);
        $this->assertNull($resultado->motivoRejeicao);
    }

    public function test_aprova_com_score_medio_aplicando_a_taxa_padrao(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 8000, valorSolicitado: 10000, score: 550);

        $this->assertTrue($resultado->foiAprovada());
        $this->assertSame(4.5, $resultado->simulacao->taxaJuros);
        $this->assertSame(1283.33, $resultado->simulacao->valorParcela);
    }

    public function test_reprova_quando_a_renda_esta_abaixo_do_minimo(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 1499.99, valorSolicitado: 1000, score: 850);

        $this->assertFalse($resultado->foiAprovada());
        $this->assertSame(StatusAnalise::REPROVADO, $resultado->status);
        $this->assertSame(PoliticaCredito::MOTIVO_RENDA_INSUFICIENTE, $resultado->motivoRejeicao);
        $this->assertNull($resultado->simulacao);
    }

    public function test_reprova_quando_o_score_esta_abaixo_do_minimo(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 8000, valorSolicitado: 1000, score: 399);

        $this->assertFalse($resultado->foiAprovada());
        $this->assertSame(PoliticaCredito::MOTIVO_SCORE_BAIXO, $resultado->motivoRejeicao);
    }

    public function test_reprova_quando_a_parcela_compromete_mais_de_30_por_cento_da_renda(): void
    {
        // Parcela de R$ 1.123,33 contra um teto de R$ 600,00.
        $resultado = $this->politica->avaliar(rendaMensal: 2000, valorSolicitado: 10000, score: 850);

        $this->assertFalse($resultado->foiAprovada());
        $this->assertSame(PoliticaCredito::MOTIVO_COMPROMETIMENTO, $resultado->motivoRejeicao);
    }

    public function test_a_renda_insuficiente_tem_precedencia_sobre_o_score_baixo(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 1000, valorSolicitado: 1000, score: 100);

        $this->assertSame(PoliticaCredito::MOTIVO_RENDA_INSUFICIENTE, $resultado->motivoRejeicao);
    }

    public function test_renda_exatamente_no_minimo_e_aprovada(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 1500, valorSolicitado: 1000, score: 850);

        $this->assertTrue($resultado->foiAprovada());
    }

    public function test_score_400_e_o_primeiro_aprovado_e_usa_a_taxa_padrao(): void
    {
        $resultado = $this->politica->avaliar(rendaMensal: 8000, valorSolicitado: 1000, score: 400);

        $this->assertTrue($resultado->foiAprovada());
        $this->assertSame(4.5, $resultado->simulacao->taxaJuros);
    }

    public function test_score_699_ainda_usa_a_taxa_padrao_e_700_muda_para_a_reduzida(): void
    {
        $renda = 8000;
        $valor = 1000;

        $this->assertSame(4.5, $this->politica->avaliar($renda, $valor, 699)->simulacao->taxaJuros);
        $this->assertSame(2.9, $this->politica->avaliar($renda, $valor, 700)->simulacao->taxaJuros);
    }

    public function test_o_teto_de_comprometimento_e_estrito(): void
    {
        // 10.000 a 2,9% em 12x => parcela de R$ 1.123,33. A renda é derivada dela
        // para deixar o teto de 30% um centavo acima e um centavo abaixo da parcela.
        $parcela = 1123.33;

        $abaixoDoTeto = $this->politica->avaliar(($parcela + 0.01) / 0.30, 10000, 850);
        $acimaDoTeto = $this->politica->avaliar(($parcela - 0.01) / 0.30, 10000, 850);

        $this->assertTrue($abaixoDoTeto->foiAprovada());
        $this->assertSame(PoliticaCredito::MOTIVO_COMPROMETIMENTO, $acimaDoTeto->motivoRejeicao);
    }
}
