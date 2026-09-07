<?php

namespace App\Services\Credito;

use App\Enums\StatusAnalise;

final readonly class ResultadoAnalise
{
    private function __construct(
        public StatusAnalise $status,
        public ?SimulacaoParcelamento $simulacao,
        public ?string $motivoRejeicao,
    ) {}

    public static function aprovada(SimulacaoParcelamento $simulacao): self
    {
        return new self(StatusAnalise::APROVADO, $simulacao, null);
    }

    public static function reprovada(string $motivo): self
    {
        return new self(StatusAnalise::REPROVADO, null, $motivo);
    }

    public function foiAprovada(): bool
    {
        return $this->status === StatusAnalise::APROVADO;
    }
}
