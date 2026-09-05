<?php

namespace Tests\Feature;

use App\Models\AnaliseCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_analise_responde_com_o_formulario(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('form-analise', escape: false);
    }

    public function test_simulacao_exibe_as_condicoes_de_uma_analise_aprovada(): void
    {
        $analise = AnaliseCredito::factory()->aprovada()->create(['valor_solicitado' => 10000]);

        $this->get("/simulacao/{$analise->id}")
            ->assertOk()
            ->assertViewIs('simulacao')
            ->assertViewHas('analise')
            ->assertSee('1.123,33')
            ->assertSee('btn-confirmar', escape: false);
    }

    public function test_simulacao_redireciona_quando_a_analise_nao_esta_aprovada(): void
    {
        $analise = AnaliseCredito::factory()->reprovada()->create();

        $this->get("/simulacao/{$analise->id}")
            ->assertRedirect('/')
            ->assertSessionHas('erro');
    }

    public function test_simulacao_de_analise_inexistente_devolve_404(): void
    {
        $this->get('/simulacao/999999')->assertNotFound();
    }
}
