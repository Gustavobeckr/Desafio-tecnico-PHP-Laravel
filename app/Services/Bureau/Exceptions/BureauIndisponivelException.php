<?php

namespace App\Services\Bureau\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Falha ao obter o score. Cobre indisponibilidade de rede, erro HTTP e
 * resposta fora do contrato — para o domínio, os três significam o mesmo:
 * não há score, então a análise não pode ser concluída agora.
 */
final class BureauIndisponivelException extends RuntimeException
{
    public static function semResposta(Throwable $anterior): self
    {
        return new self('Não foi possível se comunicar com o Bureau de Crédito.', previous: $anterior);
    }

    public static function respostaHttp(int $status): self
    {
        return new self("O Bureau de Crédito respondeu com o status HTTP {$status}.");
    }

    public static function respostaMalformada(): self
    {
        return new self('A resposta do Bureau de Crédito não contém um score válido.');
    }
}
