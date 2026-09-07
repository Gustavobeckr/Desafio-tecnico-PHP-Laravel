<?php

namespace App\Jobs;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finaliza a contratação fora do ciclo da requisição.
 *
 * Recebe o id, e não o model: o payload do job fica no banco até o worker
 * pegá-lo, e nesse intervalo os dados da análise podem mudar. Buscar na
 * execução garante que o job trabalhe com o estado atual.
 */
class ProcessarContratacaoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public int $analiseId) {}

    public function handle(): void
    {
        $analise = AnaliseCredito::find($this->analiseId);

        if ($analise === null) {
            Log::warning('Contratação ignorada: análise não encontrada.', [
                'analise_id' => $this->analiseId,
            ]);

            return;
        }

        // Torna o job idempotente: uma reexecução (retry, mensagem duplicada)
        // não reprocessa uma análise que já saiu desse estado.
        if ($analise->status !== StatusAnalise::PROCESSANDO_CONTRATACAO) {
            Log::info('Contratação ignorada: análise fora do estado esperado.', [
                'analise_id' => $analise->id,
                'status_atual' => $analise->status->value,
            ]);

            return;
        }

        $analise->update(['status' => StatusAnalise::CONTRATADO]);

        Log::info('Contratação concluída.', [
            'analise_id' => $analise->id,
            'cliente_id' => $analise->cliente_id,
            'valor_solicitado' => (float) $analise->valor_solicitado,
            'valor_parcela' => (float) $analise->valor_parcela,
        ]);
    }

    public function failed(?Throwable $excecao): void
    {
        Log::error('Falha ao processar a contratação após todas as tentativas.', [
            'analise_id' => $this->analiseId,
            'erro' => $excecao?->getMessage(),
        ]);
    }
}
