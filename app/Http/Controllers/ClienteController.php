<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ClienteResource::collection(Cliente::latest()->paginate());
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
        return ClienteResource::make($cliente);
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
