<?php

namespace App\Services\Credito;

/**
 * Juros simples sobre o valor solicitado, diluídos em parcelas fixas.
 */
final readonly class SimulacaoParcelamento
{
    public const PARCELAS = 12;

    private function __construct(
        public float $valorSolicitado,
        public float $taxaJuros,
        public float $jurosTotais,
        public float $valorTotal,
        public float $valorParcela,
    ) {}

    /**
     * @param  float  $taxaJuros  Taxa mensal em pontos percentuais (ex.: 2.9 para 2,9% a.m.).
     */
    public static function calcular(float $valorSolicitado, float $taxaJuros): self
    {
        $jurosTotais = round($valorSolicitado * ($taxaJuros / 100) * self::PARCELAS, 2);
        $valorTotal = round($valorSolicitado + $jurosTotais, 2);

        return new self(
            valorSolicitado: $valorSolicitado,
            taxaJuros: $taxaJuros,
            jurosTotais: $jurosTotais,
            valorTotal: $valorTotal,
            valorParcela: round($valorTotal / self::PARCELAS, 2),
        );
    }
}
