<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $clientes = Cliente::query()
            ->when($request->string('busca')->trim()->value(), function ($consulta, string $busca) {
                // O CPF só entra na busca quando o termo é composto apenas de dígitos e pontuação. Extrair dígitos de qualquer texto faria
                // "Cliente E2E" virar LIKE '%2%', que casa com quase tudo.
                $digitos = preg_match('/^[\d.\-\s]+$/', $busca) === 1
                    ? preg_replace('/\D/', '', $busca)
                    : '';

                $consulta->where(function ($q) use ($busca, $digitos) {
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");

                    if ($digitos !== '') {
                        $q->orWhere('cpf', 'like', "%{$digitos}%");
                    }
                });
            })
            ->withCount('analises')
            ->latest()
            ->paginate()
            ->withQueryString();

        return ClienteResource::collection($clientes);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->validated());

        return ClienteResource::make($cliente)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cliente $cliente): ClienteResource
    {
        return ClienteResource::make($cliente->load(['analises' => fn ($q) => $q->latest()]));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): ClienteResource
    {
        $cliente->update($request->validated());

        return ClienteResource::make($cliente);
    }

    public function destroy(Cliente $cliente): Response
    {
        $cliente->delete();

        return response()->noContent();
    }
}
