<?php

namespace Tests\Unit;

use App\Services\Bureau\BureauCreditoGateway;
use App\Services\Bureau\Exceptions\BureauIndisponivelException;
use App\Services\Bureau\HttpBureauCreditoGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpBureauCreditoGatewayTest extends TestCase
{
    private const URL = 'http://bureau.test/api/mock/bureau';

    private function gateway(): HttpBureauCreditoGateway
    {
        return new HttpBureauCreditoGateway(self::URL, timeout: 3);
    }

    public function test_devolve_o_score_retornado_pelo_bureau(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['cpf' => '12345678903', 'score' => 850])]);

        $consulta = $this->gateway()->consultarScore('12345678903');

        $this->assertSame(850, $consulta->score);
        $this->assertSame('12345678903', $consulta->cpf);
        Http::assertSent(fn ($request) => $request->url() === self::URL.'/12345678903');
    }

    public function test_aceita_score_devolvido_como_string(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['score' => '550'])]);

        $this->assertSame(550, $this->gateway()->consultarScore('12345678902')->score);
    }

    public function test_falha_quando_o_bureau_responde_com_erro_http(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(BureauIndisponivelException::class);
        $this->expectExceptionMessage('status HTTP 500');

        $this->gateway()->consultarScore('12345678904');
    }

    public function test_falha_quando_a_conexao_estoura_o_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->expectException(BureauIndisponivelException::class);

        $this->gateway()->consultarScore('12345678905');
    }

    public function test_falha_quando_a_resposta_nao_traz_score(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['cpf' => '12345678906', 'status_bureau' => 'ok'])]);

        $this->expectException(BureauIndisponivelException::class);
        $this->expectExceptionMessage('não contém um score válido');

        $this->gateway()->consultarScore('12345678906');
    }

    public function test_o_container_resolve_a_interface_com_a_implementacao_http(): void
    {
        $this->assertInstanceOf(
            HttpBureauCreditoGateway::class,
            $this->app->make(BureauCreditoGateway::class),
        );
    }
}
