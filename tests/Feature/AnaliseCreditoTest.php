<?php

namespace Tests\Feature;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * O fluxo completo é coberto na Fase 5; este teste garante que o endpoint
     * saiu do stub 501 e devolve a análise avaliada.
     */
    public function test_solicitar_analise_avalia_e_persiste_o_resultado(): void
    {
        Http::fake(['*/api/mock/bureau/*' => Http::response(['score' => 850])]);

        $response = $this->postJson('/api/analise-credito', [
            'cpf' => '12345678903',
            'nome' => 'João da Silva',
            'renda_mensal' => 8000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 10000.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'aprovado')
            ->assertJsonPath('data.score', 850)
            ->assertJsonPath('data.taxa_juros', 2.9)
            ->assertJsonPath('data.valor_parcela', 1123.33);

        $this->assertDatabaseHas('clientes', ['cpf' => '12345678903']);
    }

    public function test_falha_do_bureau_devolve_503_e_mantem_a_analise_pendente(): void
    {
        Http::fake(['*/api/mock/bureau/*' => Http::response(['error' => 'indisponível'], 500)]);

        $this->postJson('/api/analise-credito', [
            'cpf' => '12345678904',
            'nome' => 'Rita Gomes',
            'renda_mensal' => 8000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
        ])->assertServiceUnavailable();

        $this->assertDatabaseHas('analises_credito', [
            'cpf' => '12345678904',
            'status' => StatusAnalise::PENDENTE->value,
            'score' => null,
        ]);
    }

    public function test_timeout_do_bureau_devolve_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->postJson('/api/analise-credito', [
            'cpf' => '12345678905',
            'nome' => 'Tiago Pires',
            'renda_mensal' => 8000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
        ])->assertServiceUnavailable();
    }

    public function test_contratar_analise_aprovada_atualiza_o_status(): void
    {
        $analise = AnaliseCredito::factory()->aprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")
            ->assertOk()
            ->assertJsonPath('data.status', StatusAnalise::CONTRATADO->value);

        $this->assertSame(StatusAnalise::CONTRATADO, $analise->refresh()->status);
    }

    public function test_contratar_analise_nao_aprovada_devolve_422(): void
    {
        $analise = AnaliseCredito::factory()->reprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")
            ->assertUnprocessable();

        $this->assertSame(StatusAnalise::REPROVADO, $analise->refresh()->status);
    }

    public function test_contratar_analise_inexistente_devolve_404(): void
    {
        $this->postJson('/api/analise-credito/999999/contratar')->assertNotFound();
    }

    /**
     * DICA PARA O CANDIDATO:
     * Crie aqui testes adicionais para cobrir os fluxos de sucesso e erro:
     *
     * 1. Testar análise de crédito aprovada com score alto (juros de 2.9%).
     * 2. Testar análise de crédito aprovada com score médio (juros de 4.5%).
     * 3. Testar reprovação por renda mensal insuficiente (abaixo de R$ 1.500,00).
     * 4. Testar reprovação por score muito baixo (abaixo de 400).
     * 5. Testar reprovação por comprometimento de renda (parcela > 30% da renda).
     * 6. Testar resiliência caso a API externa do Bureau retorne erro 500.
     * 7. Testar se a rota de contratação dispara o Job `ProcessarContratacaoJob` para a fila.
     *
     * Lembre-se de utilizar \Illuminate\Support\Facades\Http::fake() para simular as chamadas à API do Bureau.
     */
}
