<?php

namespace App\Http\Requests;

use App\Enums\TipoCredito;
use App\Http\Requests\Concerns\NormalizaCpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitarAnaliseRequest extends FormRequest
{
    use NormalizaCpf;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'digits:11'],
            'renda_mensal' => ['required', 'numeric', 'min:0'],
            'tipo_credito' => ['required', Rule::enum(TipoCredito::class)],
            'valor_solicitado' => ['required', 'numeric', 'gt:0'],
            // O formulário não coleta e-mail, mas é aceito para o cadastro
            // automático do cliente quando o consumidor da API o envia.
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
