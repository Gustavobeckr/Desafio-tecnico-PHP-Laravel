<?php

namespace App\Services\Bureau;

use App\Services\Bureau\Exceptions\BureauIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class HttpBureauCreditoGateway implements BureauCreditoGateway
{
    public function __construct(
        private string $url,
        private int $timeout,
    ) {}

    public function consultarScore(string $cpf): ConsultaBureau
    {
        try {
            return $this->consultar($cpf);
        } catch (BureauIndisponivelException $e) {
            Log::warning('Consulta ao Bureau de Crédito falhou.', [
                'cpf' => self::mascarar($cpf),
                'motivo' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function consultar(string $cpf): ConsultaBureau
    {
        try {
            $resposta = Http::acceptJson()
                ->connectTimeout($this->timeout)
                ->timeout($this->timeout)
                ->get("{$this->url}/{$cpf}");
        } catch (ConnectionException $e) {
            throw BureauIndisponivelException::semResposta($e);
        }

        if ($resposta->failed()) {
            throw BureauIndisponivelException::respostaHttp($resposta->status());
        }

        $score = $resposta->json('score');

        if (! is_numeric($score)) {
            throw BureauIndisponivelException::respostaMalformada();
        }

        return new ConsultaBureau($cpf, (int) $score);
    }

    private static function mascarar(string $cpf): string
    {
        return substr($cpf, 0, 3).'******'.substr($cpf, -2);
    }
}
