<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'cpf' => static::cpf(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('51 9####-####'),
            'renda_mensal' => fake()->randomFloat(2, 5000, 20000),
        ];
    }

    /**
     * O mock do Bureau decide o score pelo último dígito do CPF, então os testes
     * precisam controlar esse dígito para exercitar cada cenário de resposta.
     */
    public function comUltimoDigito(int $digito): static
    {
        return $this->state(fn () => [
            'cpf' => substr(static::cpf(), 0, 10).$digito,
        ]);
    }

    public function comRenda(float $renda): static
    {
        return $this->state(fn () => ['renda_mensal' => $renda]);
    }

    /**
     * CPF com 11 dígitos, sem máscara e sem validação de dígito verificador —
     * o domínio do desafio só exige o formato.
     */
    public static function cpf(): string
    {
        return (string) fake()->unique()->numberBetween(10000000000, 99999999999);
    }
}
