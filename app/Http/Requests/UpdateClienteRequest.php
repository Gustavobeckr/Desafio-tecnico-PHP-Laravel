<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    use NormalizaCpf;

    /**
     * `sometimes` permite atualização parcial: o campo ausente é ignorado,
     * o campo presente continua obrigado a ser válido.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $cliente = $this->route('cliente');

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'cpf' => ['sometimes', 'required', 'digits:11', Rule::unique('clientes')->ignore($cliente)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('clientes')->ignore($cliente)],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'renda_mensal' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Rótulo próprio do cadastro de clientes; os demais campos vêm de lang/pt_BR/validation.php.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'telefone' => 'telefone',
        ];
    }
}
