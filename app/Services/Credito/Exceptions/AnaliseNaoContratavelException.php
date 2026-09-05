<?php

namespace App\Services\Credito\Exceptions;

use App\Enums\StatusAnalise;
use DomainException;

final class AnaliseNaoContratavelException extends DomainException
{
    public static function status(StatusAnalise $atual): self
    {
        return new self(
            "Somente análises aprovadas podem ser contratadas. Status atual: {$atual->value}."
        );
    }
}
