<?php

namespace App\Http\Resources;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cliente
 */
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'renda_mensal' => (float) $this->renda_mensal,
            'analises_count' => $this->whenCounted('analises'),
            'analises' => AnaliseCreditoResource::collection($this->whenLoaded('analises')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
