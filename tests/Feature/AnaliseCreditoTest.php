<?php

namespace Tests\Feature;

use App\Enums\StatusAnalise;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use App\Services\Credito\PoliticaCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

    public function test_aprova_com_score_alto_aplicando_a_taxa_reduzida(): void
    {
        $this->bureauResponde(850);

        $this->postJson('/api/analise-credito', $this->dados())
            ->assertCreated()
            ->assertJsonPath('data.status', StatusAnalise::APROVADO->value)
            ->assertJsonPath('data.score', 850)
            ->assertJsonPath('data.taxa_juros', PoliticaCredito::TAXA_REDUZIDA)
            ->assertJsonPath('data.valor_parcela', 1123.33)
            ->assertJsonPath('data.comprometimento_renda', 14.04)
            ->assertJsonPath('data.motivo_rejeicao', null);
    }

    public function test_aprova_com_score_medio_aplicando_a_taxa_padrao(): void
    {
        $this->bureauResponde(550);

        $this->postJson('/api/analise-credito', $this->dados(['cpf' => '12345678902']))
            ->assertCreated()
            ->assertJsonPath('data.status', StatusAnalise::APROVADO->value)
            ->assertJsonPath('data.taxa_juros', PoliticaCredito::TAXA_PADRAO)
            ->assertJsonPath('data.valor_parcela', 1283.33);
    }

    public function test_reprova_por_renda_mensal_insuficiente(): void
    {
        $this->bureauResponde(850);

        $this->postJson('/api/analise-credito', $this->dados(['renda_mensal' => 1000, 'valor_solicitado' => 5000]))
            ->assertCreated()
            ->assertJsonPath('data.status', StatusAnalise::REPROVADO->value)
            ->assertJsonPath('data.motivo_rejeicao', PoliticaCredito::MOTIVO_RENDA_INSUFICIENTE)
            ->assertJsonPath('data.taxa_juros', null)
            ->assertJsonPath('data.valor_parcela', null);
    }

    public function test_reprova_por_score_baixo(): void
    {
        $this->bureauResponde(150);

        $this->postJson('/api/analise-credito', $this->dados(['cpf' => '12345678901']))
            ->assertCreated()
            ->assertJsonPath('data.status', StatusAnalise::REPROVADO->value)
            ->assertJsonPath('data.score', 150)
            ->assertJsonPath('data.motivo_rejeicao', PoliticaCredito::MOTIVO_SCORE_BAIXO);
    }

    public function test_reprova_por_comprometimento_de_renda(): void
    {
        $this->bureauResponde(850);

        // Parcela de R$ 1.123,33 contra um teto de R$ 600,00 (30% de R$ 2.000).
        $this->postJson('/api/analise-credito', $this->dados(['renda_mensal' => 2000]))
            ->assertCreated()
            ->assertJsonPath('data.status', StatusAnalise::REPROVADO->value)
            ->assertJsonPath('data.motivo_rejeicao', PoliticaCredito::MOTIVO_COMPROMETIMENTO);
    }

    public function test_consulta_o_bureau_com_o_cpf_informado(): void
    {
        $this->bureauResponde(850);

        $this->postJson('/api/analise-credito', $this->dados())->assertCreated();

        Http::assertSentCount(1);
        Http::assertSent(fn ($requisicao) => str_ends_with($requisicao->url(), '/api/mock/bureau/12345678903'));
    }

    public function test_cria_o_cliente_automaticamente_quando_o_cpf_e_novo(): void
    {
        $this->bureauResponde(850);

        $this->assertDatabaseCount('clientes', 0);

        $this->postJson('/api/analise-credito', $this->dados())->assertCreated();

        $this->assertDatabaseHas('clientes', [
            'cpf' => '12345678903',
            'nome' => 'João da Silva',
        ]);

        $analise = AnaliseCredito::sole();
        $this->assertNotNull($analise->cliente_id);
        $this->assertSame('12345678903', $analise->cliente->cpf);
    }

    public function test_reaproveita_o_cliente_existente_quando_o_cpf_ja_esta_cadastrado(): void
    {
        $this->bureauResponde(850);
        $cliente = Cliente::factory()->create(['cpf' => '12345678903']);

        $this->postJson('/api/analise-credito', $this->dados())->assertCreated();

        $this->assertDatabaseCount('clientes', 1);
        $this->assertSame($cliente->id, AnaliseCredito::sole()->cliente_id);
    }

    public function test_falha_de_validacao_nao_cria_analise_nem_consulta_o_bureau(): void
    {
        $this->postJson('/api/analise-credito', ['cpf' => '123', 'tipo_credito' => 'consignado', 'valor_solicitado' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'cpf', 'renda_mensal', 'tipo_credito', 'valor_solicitado']);

        $this->assertDatabaseCount('analises_credito', 0);
        Http::assertNothingSent();
    }

    public function test_erro_http_do_bureau_devolve_503_e_mantem_a_analise_pendente(): void
    {
        Http::fake(['*/api/mock/bureau/*' => Http::response(['error' => 'indisponível'], 500)]);

        $this->postJson('/api/analise-credito', $this->dados(['cpf' => '12345678904']))
            ->assertServiceUnavailable()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('analises_credito', [
            'cpf' => '12345678904',
            'status' => StatusAnalise::PENDENTE->value,
            'score' => null,
        ]);
    }

    public function test_timeout_do_bureau_devolve_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->postJson('/api/analise-credito', $this->dados(['cpf' => '12345678905']))
            ->assertServiceUnavailable();

        $this->assertDatabaseHas('analises_credito', ['status' => StatusAnalise::PENDENTE->value]);
    }

    public function test_resposta_do_bureau_sem_score_devolve_503(): void
    {
        Http::fake(['*/api/mock/bureau/*' => Http::response(['cpf' => '12345678906', 'status_bureau' => 'ok'])]);

        $this->postJson('/api/analise-credito', $this->dados(['cpf' => '12345678906']))
            ->assertServiceUnavailable();

        $this->assertDatabaseHas('analises_credito', ['status' => StatusAnalise::PENDENTE->value]);
    }

    public function test_contratar_analise_aprovada_envia_para_a_fila(): void
    {
        Queue::fake();
        $analise = AnaliseCredito::factory()->aprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")
            ->assertOk()
            ->assertJsonPath('data.id', $analise->id)
            ->assertJsonPath('data.status', StatusAnalise::PROCESSANDO_CONTRATACAO->value);

        $this->assertSame(StatusAnalise::PROCESSANDO_CONTRATACAO, $analise->refresh()->status);

        Queue::assertPushed(
            ProcessarContratacaoJob::class,
            fn (ProcessarContratacaoJob $job) => $job->analiseId === $analise->id,
        );
    }

    public function test_contratar_com_a_fila_executando_chega_em_contratado(): void
    {
        // QUEUE_CONNECTION=sync no phpunit.xml: sem Queue::fake() o job roda
        // na hora, simulando o worker.
        $analise = AnaliseCredito::factory()->aprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")->assertOk();

        $this->assertSame(StatusAnalise::CONTRATADO, $analise->refresh()->status);
    }

    public function test_contratar_analise_reprovada_devolve_422(): void
    {
        $analise = AnaliseCredito::factory()->reprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);

        $this->assertSame(StatusAnalise::REPROVADO, $analise->refresh()->status);
    }

    public function test_contratar_analise_pendente_devolve_422(): void
    {
        $analise = AnaliseCredito::factory()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")->assertUnprocessable();

        $this->assertSame(StatusAnalise::PENDENTE, $analise->refresh()->status);
    }

    public function test_contratar_duas_vezes_devolve_422_na_segunda(): void
    {
        Queue::fake();
        $analise = AnaliseCredito::factory()->aprovada()->create();

        $this->postJson("/api/analise-credito/{$analise->id}/contratar")->assertOk();
        $this->postJson("/api/analise-credito/{$analise->id}/contratar")->assertUnprocessable();

        Queue::assertPushed(ProcessarContratacaoJob::class, 1);
    }

    public function test_contratar_analise_inexistente_devolve_404(): void
    {
        $this->postJson('/api/analise-credito/999999/contratar')
            ->assertNotFound()
            ->assertJsonPath('message', 'Registro não encontrado.');
    }

    private function bureauResponde(int $score): void
    {
        Http::fake(['*/api/mock/bureau/*' => Http::response(['score' => $score, 'situacao' => 'ativo'])]);
    }

    /**
     * @param  array<string, mixed>  $sobrescreve
     * @return array<string, mixed>
     */
    private function dados(array $sobrescreve = []): array
    {
        return array_merge([
            'nome' => 'João da Silva',
            'cpf' => '12345678903',
            'renda_mensal' => 8000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 10000.00,
        ], $sobrescreve);
    }
}
