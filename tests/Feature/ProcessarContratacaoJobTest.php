<?php

namespace Tests\Feature;

use App\Enums\StatusAnalise;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessarContratacaoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_finaliza_a_analise_em_processamento(): void
    {
        $analise = AnaliseCredito::factory()->aprovada()->create([
            'status' => StatusAnalise::PROCESSANDO_CONTRATACAO,
        ]);

        (new ProcessarContratacaoJob($analise->id))->handle();

        $this->assertSame(StatusAnalise::CONTRATADO, $analise->refresh()->status);
    }

    public function test_e_idempotente_e_nao_reprocessa_analise_ja_contratada(): void
    {
        $analise = AnaliseCredito::factory()->contratada()->create();
        $atualizadoEm = $analise->updated_at;

        (new ProcessarContratacaoJob($analise->id))->handle();

        $this->assertSame(StatusAnalise::CONTRATADO, $analise->refresh()->status);
        $this->assertEquals($atualizadoEm, $analise->updated_at);
    }

    public function test_ignora_analise_em_status_inesperado(): void
    {
        $analise = AnaliseCredito::factory()->reprovada()->create();

        (new ProcessarContratacaoJob($analise->id))->handle();

        $this->assertSame(StatusAnalise::REPROVADO, $analise->refresh()->status);
    }

    public function test_nao_quebra_quando_a_analise_nao_existe(): void
    {
        (new ProcessarContratacaoJob(999999))->handle();

        $this->assertDatabaseCount('analises_credito', 0);
    }
}
