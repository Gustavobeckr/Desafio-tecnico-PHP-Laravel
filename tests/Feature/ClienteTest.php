<?php

namespace Tests\Feature;

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
