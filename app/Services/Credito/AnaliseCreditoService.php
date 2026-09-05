<?php

namespace App\Services\Credito;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use App\Services\Bureau\BureauCreditoGateway;
use App\Services\Bureau\Exceptions\BureauIndisponivelException;
use App\Services\Credito\Exceptions\AnaliseNaoContratavelException;
use Illuminate\Support\Facades\DB;

final readonly class AnaliseCreditoService
{
    public function __construct(
        private BureauCreditoGateway $bureau,
        private PoliticaCredito $politica,
    ) {}

    /**
     * @param  array<string, mixed>  $dados  Já validados por SolicitarAnaliseRequest.
     *
     * @throws BureauIndisponivelException A análise fica registrada como pendente.
     */
    public function solicitar(array $dados): AnaliseCredito
    {
        $analise = $this->registrar($dados);

        // Fora da transação: I/O de rede não deve manter uma transação aberta.
        $consulta = $this->bureau->consultarScore($analise->cpf);

        $resultado = $this->politica->avaliar(
            rendaMensal: (float) $analise->renda_mensal,
            valorSolicitado: (float) $analise->valor_solicitado,
            score: $consulta->score,
        );

        $analise->update([
            'score' => $consulta->score,
            'status' => $resultado->status,
            'taxa_juros' => $resultado->simulacao?->taxaJuros,
            'valor_parcela' => $resultado->simulacao?->valorParcela,
            'motivo_rejeicao' => $resultado->motivoRejeicao,
        ]);

        return $analise;
    }

    /**
     * @throws AnaliseNaoContratavelException
     */
    public function contratar(AnaliseCredito $analise): AnaliseCredito
    {
        if ($analise->status !== StatusAnalise::APROVADO) {
            throw AnaliseNaoContratavelException::status($analise->status);
        }

        $analise->update(['status' => StatusAnalise::CONTRATADO]);

        return $analise;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function registrar(array $dados): AnaliseCredito
    {
        return DB::transaction(function () use ($dados): AnaliseCredito {
            $cliente = Cliente::firstOrCreate(
                ['cpf' => $dados['cpf']],
                [
                    'nome' => $dados['nome'],
                    'email' => $dados['email'] ?? null,
                    'renda_mensal' => $dados['renda_mensal'],
                ],
            );

            return $cliente->analises()->create([
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'renda_mensal' => $dados['renda_mensal'],
                'tipo_credito' => $dados['tipo_credito'],
                'valor_solicitado' => $dados['valor_solicitado'],
                'status' => StatusAnalise::PENDENTE,
            ]);
        });
    }
}
