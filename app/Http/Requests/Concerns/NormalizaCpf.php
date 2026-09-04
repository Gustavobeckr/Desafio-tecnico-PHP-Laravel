<?php

namespace App\Http\Requests\Concerns;

trait NormalizaCpf
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
        }
    }
}
