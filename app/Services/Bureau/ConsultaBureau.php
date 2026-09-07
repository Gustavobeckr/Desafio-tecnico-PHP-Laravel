<?php

namespace App\Services\Bureau;

final readonly class ConsultaBureau
{
    public function __construct(
        public string $cpf,
        public int $score,
    ) {}
}
