<?php

namespace App\Services\Bureau;

use App\Services\Bureau\Exceptions\BureauIndisponivelException;

interface BureauCreditoGateway
{
    /**
     * @throws BureauIndisponivelException
     */
    public function consultarScore(string $cpf): ConsultaBureau;
}
