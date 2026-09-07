<?php

namespace Tests\Feature;

use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_cliente_com_dados_validos(): void
    {
        $this->postJson('/api/clientes', $this->dados())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Maria Oliveira')
            ->assertJsonPath('data.cpf', '52998224725')
            ->assertJsonPath('data.renda_mensal', 8000.5);

        $this->assertDatabaseHas('clientes', [
            'cpf' => '52998224725',
            'email' => 'maria.oliveira@example.com',
        ]);
    }

    public function test_normaliza_o_cpf_removendo_a_mascara(): void
    {
        $this->postJson('/api/clientes', $this->dados(['cpf' => '529.982.247-25']))
            ->assertCreated()
            ->assertJsonPath('data.cpf', '52998224725');

        $this->assertDatabaseHas('clientes', ['cpf' => '52998224725']);
    }

    public function test_falha_ao_criar_cliente_sem_campos_obrigatorios(): void
    {
        $this->postJson('/api/clientes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'cpf', 'email', 'renda_mensal']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_falha_ao_criar_cliente_com_cpf_fora_do_formato(): void
    {
        $this->postJson('/api/clientes', $this->dados(['cpf' => '123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');
    }

    public function test_falha_ao_criar_cliente_com_renda_negativa(): void
    {
        $this->postJson('/api/clientes', $this->dados(['renda_mensal' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('renda_mensal');
    }

    public function test_falha_ao_criar_cliente_com_cpf_duplicado(): void
    {
        $existente = Cliente::factory()->create();

        $this->postJson('/api/clientes', $this->dados(['cpf' => $existente->cpf]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_falha_ao_criar_cliente_com_email_duplicado(): void
    {
        $existente = Cliente::factory()->create();

        $this->postJson('/api/clientes', $this->dados(['email' => $existente->email]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_lista_clientes_de_forma_paginada(): void
    {
        Cliente::factory()->count(20)->create();

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure([
                'data' => [['id', 'nome', 'cpf', 'email', 'telefone', 'renda_mensal', 'created_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_exibe_cliente_existente(): void
    {
        $cliente = Cliente::factory()->create();

        $this->getJson("/api/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $cliente->id)
            ->assertJsonPath('data.cpf', $cliente->cpf);
    }

    public function test_retorna_404_ao_buscar_cliente_inexistente(): void
    {
        $this->getJson('/api/clientes/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Registro não encontrado.');
    }

    public function test_atualiza_parcialmente_um_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Nome Antigo']);

        $this->putJson("/api/clientes/{$cliente->id}", ['renda_mensal' => 12345.67])
            ->assertOk()
            ->assertJsonPath('data.renda_mensal', 12345.67)
            ->assertJsonPath('data.nome', 'Nome Antigo');

        $this->assertSame('12345.67', $cliente->refresh()->renda_mensal);
    }

    public function test_atualizacao_aceita_manter_o_proprio_email(): void
    {
        $cliente = Cliente::factory()->create();

        $this->putJson("/api/clientes/{$cliente->id}", [
            'email' => $cliente->email,
            'nome' => 'Nome Atualizado',
        ])->assertOk()->assertJsonPath('data.nome', 'Nome Atualizado');
    }

    public function test_atualizacao_rejeita_email_de_outro_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $outro = Cliente::factory()->create();

        $this->putJson("/api/clientes/{$cliente->id}", ['email' => $outro->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_retorna_404_ao_atualizar_cliente_inexistente(): void
    {
        $this->putJson('/api/clientes/999999', ['nome' => 'X'])->assertNotFound();
    }

    public function test_remove_cliente_existente(): void
    {
        $cliente = Cliente::factory()->create();

        $this->deleteJson("/api/clientes/{$cliente->id}")->assertNoContent();

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_retorna_404_ao_remover_cliente_inexistente(): void
    {
        $this->deleteJson('/api/clientes/999999')->assertNotFound();
    }

    public function test_busca_por_nome_email_e_cpf(): void
    {
        $alvo = Cliente::factory()->create([
            'nome' => 'Maria Oliveira',
            'cpf' => '52998224725',
            'email' => 'maria@example.com',
        ]);
        // Nomes e CPFs fixos: o faker pt_BR gera "Maria" com frequência e
        // deixaria a asserção de total instável.
        Cliente::factory()
            ->count(5)
            ->sequence(fn ($sequencia) => [
                'nome' => 'Fulano '.$sequencia->index,
                'cpf' => '1000000000'.$sequencia->index,
            ])
            ->create();

        foreach (['Maria', 'maria@example.com', '52998224725', '529.982', '529982'] as $termo) {
            $this->getJson('/api/clientes?busca='.urlencode($termo))
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', $alvo->id);
        }
    }

    public function test_busca_com_digito_no_meio_do_texto_nao_vira_filtro_de_cpf(): void
    {
        // "Cliente E2E" contém o dígito 2; extrair dígitos de qualquer texto
        // faria a busca virar cpf LIKE '%2%' e casar com quase tudo.
        Cliente::factory()->create(['nome' => 'Cliente E2E']);
        Cliente::factory()
            ->count(5)
            ->sequence(fn ($sequencia) => ['nome' => 'Fulano '.$sequencia->index])
            ->create();

        $this->getJson('/api/clientes?busca='.urlencode('Cliente E2E'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_busca_sem_resultado_devolve_lista_vazia(): void
    {
        Cliente::factory()->count(3)->create();

        $this->getJson('/api/clientes?busca=inexistente')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_listagem_traz_a_contagem_de_analises_sem_o_array(): void
    {
        $cliente = Cliente::factory()->create();
        AnaliseCredito::factory()->count(2)->create(['cliente_id' => $cliente->id]);

        $resposta = $this->getJson('/api/clientes')->assertOk();

        $resposta->assertJsonPath('data.0.analises_count', 2);
        $this->assertArrayNotHasKey('analises', $resposta->json('data.0'));
    }

    public function test_detalhe_traz_o_historico_de_analises_do_mais_recente_para_o_mais_antigo(): void
    {
        $cliente = Cliente::factory()->create();
        $antiga = AnaliseCredito::factory()->reprovada()->create([
            'cliente_id' => $cliente->id,
            'created_at' => now()->subDay(),
        ]);
        $recente = AnaliseCredito::factory()->aprovada()->create(['cliente_id' => $cliente->id]);

        $this->getJson("/api/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.analises')
            ->assertJsonPath('data.analises.0.id', $recente->id)
            ->assertJsonPath('data.analises.1.id', $antiga->id);
    }

    /**
     * @param  array<string, mixed>  $sobrescreve
     * @return array<string, mixed>
     */
    private function dados(array $sobrescreve = []): array
    {
        return array_merge([
            'nome' => 'Maria Oliveira',
            'cpf' => '52998224725',
            'email' => 'maria.oliveira@example.com',
            'telefone' => '51 99999-1234',
            'renda_mensal' => 8000.50,
        ], $sobrescreve);
    }
}
