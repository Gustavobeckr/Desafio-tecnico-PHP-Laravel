<?php

namespace App\Services\Credito;

/**
 * Regras de elegibilidade do crédito. Sem I/O: recebe os números e decide.
 */
final class PoliticaCredito
{
    public const RENDA_MINIMA = 1500.0;

    public const SCORE_MINIMO = 400;

    public const SCORE_TAXA_REDUZIDA = 700;

    public const TAXA_PADRAO = 4.5;

    public const TAXA_REDUZIDA = 2.9;

    public const COMPROMETIMENTO_MAXIMO = 0.30;

    public const MOTIVO_RENDA_INSUFICIENTE = 'Renda mínima insuficiente';

    public const MOTIVO_SCORE_BAIXO = 'Score de crédito muito baixo';

    public const MOTIVO_COMPROMETIMENTO = 'Comprometimento de renda superior a 30%';

    /**
     * As condições são avaliadas na ordem do enunciado; a primeira que falhar
     * define o motivo da recusa.
     */
    public function avaliar(float $rendaMensal, float $valorSolicitado, int $score): ResultadoAnalise
    {
        if ($rendaMensal < self::RENDA_MINIMA) {
            return ResultadoAnalise::reprovada(self::MOTIVO_RENDA_INSUFICIENTE);
        }

        if ($score < self::SCORE_MINIMO) {
            return ResultadoAnalise::reprovada(self::MOTIVO_SCORE_BAIXO);
        }

        $simulacao = SimulacaoParcelamento::calcular($valorSolicitado, $this->taxaPara($score));

        if ($simulacao->valorParcela > $rendaMensal * self::COMPROMETIMENTO_MAXIMO) {
            return ResultadoAnalise::reprovada(self::MOTIVO_COMPROMETIMENTO);
        }

        return ResultadoAnalise::aprovada($simulacao);
    }

    private function taxaPara(int $score): float
    {
        return $score >= self::SCORE_TAXA_REDUZIDA ? self::TAXA_REDUZIDA : self::TAXA_PADRAO;
    }
}
