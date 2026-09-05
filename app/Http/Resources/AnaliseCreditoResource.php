<?php

namespace App\Http\Resources;

use App\Models\AnaliseCredito;
use App\Services\Credito\SimulacaoParcelamento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AnaliseCredito
 */
class AnaliseCreditoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'renda_mensal' => (float) $this->renda_mensal,
            'tipo_credito' => $this->tipo_credito->value,
            'valor_solicitado' => (float) $this->valor_solicitado,
            'parcelas' => SimulacaoParcelamento::PARCELAS,
            'status' => $this->status->value,
            'score' => $this->score,
            'taxa_juros' => $this->comoFloat($this->taxa_juros),
            'valor_parcela' => $this->comoFloat($this->valor_parcela),
            'comprometimento_renda' => $this->comprometimentoRenda(),
            'motivo_rejeicao' => $this->motivo_rejeicao,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Percentual da renda tomado pela parcela, para a tela não repetir o cálculo.
     */
    private function comprometimentoRenda(): ?float
    {
        if ($this->valor_parcela === null || (float) $this->renda_mensal <= 0.0) {
            return null;
        }

        return round((float) $this->valor_parcela / (float) $this->renda_mensal * 100, 2);
    }

    private function comoFloat(mixed $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }
}
