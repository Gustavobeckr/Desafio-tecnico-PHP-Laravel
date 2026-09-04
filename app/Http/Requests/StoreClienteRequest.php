<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    use NormalizaCpf;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'digits:11', Rule::unique('clientes')],
            'email' => ['required', 'email', 'max:255', Rule::unique('clientes')],
            'telefone' => ['nullable', 'string', 'max:20'],
            'renda_mensal' => ['required', 'numeric', 'min:0'],
        ];
    }
}
