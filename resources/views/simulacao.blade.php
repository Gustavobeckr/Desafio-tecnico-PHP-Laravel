<x-layout
    titulo="Simulação de Crédito — Coop0156"
    largura="max-w-4xl"
    fundo="radial-gradient(at 20% 20%, hsla(210, 70%, 15%, 0.2) 0px, transparent 50%),
                radial-gradient(at 80% 80%, hsla(142, 70%, 12%, 0.15) 0px, transparent 50%)"
>

    <x-slot:acao>
            <a href="/" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Nova Análise
            </a>
    </x-slot:acao>

    <main class="flex-grow max-w-4xl mx-auto px-4 py-12 w-full">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8">
            <a href="/" class="hover:text-slate-300 transition-colors">Análise</a>
            <span>/</span>
            <span class="text-slate-300">Simulação #{{ $analise->id }}</span>
        </nav>

        <!-- Cabeçalho da Simulação -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Simulação de Crédito</h2>
                <p class="text-slate-400 mt-1">Revise as condições antes de confirmar a contratação.</p>
            </div>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Pré-aprovado
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Dados do Proponente -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Proponente</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500">Nome</p>
                        <p class="font-semibold text-slate-100">{{ $analise->nome }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">CPF</p>
                        <p class="font-medium text-slate-200 font-mono">{{ $analise->cpf }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Renda Mensal</p>
                        <p class="font-medium text-slate-200">R$ {{ number_format($analise->renda_mensal, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Tipo de Crédito</p>
                        <p class="font-medium text-slate-200 capitalize">{{ $analise->tipo_credito->value }}</p>
                    </div>
                </div>
            </div>

            <!-- Score e Aprovação -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Score de Crédito</h3>
                <div class="flex flex-col items-center justify-center h-32">
                    <p class="text-6xl font-bold bg-gradient-to-b from-emerald-300 to-emerald-500 bg-clip-text text-transparent">
                        {{ $analise->score }}
                    </p>
                    <p class="text-slate-400 text-sm mt-2">Pontuação Obtida</p>
                </div>
                <div class="mt-4 pt-4 border-t border-panelBorder">
                    <p class="text-xs text-slate-500">Taxa de Juros Aplicada</p>
                    <p class="text-xl font-bold text-emerald-400 mt-1">{{ number_format($analise->taxa_juros, 1, ',', '.') }}% a.m.</p>
                </div>
            </div>

            <!-- Condições Financeiras -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Condições</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500">Valor Solicitado</p>
                        <p class="font-semibold text-slate-100 text-lg">R$ {{ number_format($analise->valor_solicitado, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Parcelas</p>
                        <p class="font-medium text-slate-200">12x fixas</p>
                    </div>
                    <div class="pt-3 border-t border-panelBorder">
                        <p class="text-xs text-slate-500">Valor Estimado da Parcela</p>
                        <p class="text-2xl font-bold text-white mt-1">
                            R$ {{ number_format($analise->valor_parcela, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aviso de Comprometimento de Renda -->
        @php
            $comprometimento = ($analise->valor_parcela / $analise->renda_mensal) * 100;
        @endphp
        <div class="glass-panel rounded-2xl p-5 mt-6 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-200">Comprometimento de renda</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    A parcela representa aproximadamente <span class="text-blue-400 font-semibold">{{ number_format($comprometimento, 1, ',', '.') }}%</span>
                    da sua renda mensal declarada (R$ {{ number_format($analise->renda_mensal, 2, ',', '.') }}).
                </p>
            </div>
        </div>

        <!-- Botão de Contratação -->
        <div class="mt-8 glass-panel rounded-2xl p-8 text-center">

            @if(session('erro'))
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6 text-red-400 text-sm">
                    {{ session('erro') }}
                </div>
            @endif

            <h3 class="text-xl font-semibold text-white mb-2">Confirmar Contratação</h3>
            <p class="text-slate-400 text-sm mb-8 max-w-md mx-auto">
                Ao confirmar, você está simulando a solicitação formal de contratação deste crédito. Esta ação não pode ser desfeita.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/" class="px-8 py-3.5 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 hover:border-slate-500 transition-all font-medium text-sm">
                    Cancelar
                </a>
                <button id="btn-confirmar"
                    class="px-10 py-3.5 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-500/20 flex items-center gap-2 justify-center">
                    <span id="txt-confirmar">Confirmar Contratação</span>
                    <svg id="spinner-confirmar" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>

    </main>


    <!-- Sucesso Modal -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="glass-panel rounded-3xl p-10 max-w-md w-full mx-4 text-center">
            <div class="h-20 w-20 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Contratação Realizada!</h3>
            <p class="text-slate-400 text-sm mb-6">O crédito foi contratado com sucesso. Você receberá uma confirmação em breve.</p>
            <div id="modal-status" class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-3 mb-6 text-xs text-emerald-400 font-mono">
                Status: CONTRATADO
            </div>
            <a href="/" class="inline-block px-8 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20 rounded-xl text-sm font-medium transition-all">
                Iniciar Nova Simulação
            </a>
        </div>
    </div>

    <x-slot:scripts>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnConfirmar = document.getElementById('btn-confirmar');
            const txtConfirmar = document.getElementById('txt-confirmar');
            const spinner = document.getElementById('spinner-confirmar');
            const modal = document.getElementById('modal-sucesso');
            const modalStatus = document.getElementById('modal-status');

            const acoes = btnConfirmar.parentElement;
            const banner = criarBanner();
            let contratada = false;

            btnConfirmar.addEventListener('click', async () => {
                banner.classList.add('hidden');
                carregando(true);

                try {
                    const resposta = await fetch('/api/analise-credito/{{ $analise->id }}/contratar', {
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    });

                    const corpo = await resposta.json().catch(() => ({}));

                    if (resposta.ok) {
                        contratada = true;
                        modalStatus.textContent = `Status: ${(corpo.data?.status ?? 'contratado').toUpperCase()}`;
                        modal.classList.remove('hidden');
                    } else {
                        exibirErro(corpo.message ?? 'Não foi possível concluir a contratação.');
                    }
                } catch {
                    exibirErro('Falha de conexão com o servidor. Tente novamente.');
                } finally {
                    carregando(false);
                }
            });

            function carregando(ativo) {
                btnConfirmar.disabled = ativo || contratada;
                btnConfirmar.classList.toggle('opacity-60', btnConfirmar.disabled);
                btnConfirmar.classList.toggle('cursor-not-allowed', btnConfirmar.disabled);
                spinner.classList.toggle('hidden', !ativo);
                txtConfirmar.textContent = ativo ? 'Processando...' : 'Confirmar Contratação';
            }

            function exibirErro(mensagem) {
                banner.textContent = mensagem;
                banner.classList.remove('hidden');
            }

            function criarBanner() {
                const elemento = document.createElement('div');
                elemento.className = 'hidden bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6 text-red-400 text-sm';
                acoes.parentNode.insertBefore(elemento, acoes);
                return elemento;
            }
        });
    </script>
    </x-slot:scripts>

</x-layout>
