<x-layout titulo="Plataforma de Crédito Cooperativo">

    <x-slot:acao>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Ambiente de Testes
                </span>
            </div>
    </x-slot:acao>

    <main class="flex-grow max-w-6xl mx-auto px-4 py-12 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Formulário de Solicitação -->
        <section class="lg:col-span-7 glass-panel rounded-3xl p-8 shadow-2xl relative overflow-hidden transition-all duration-300 hover:border-panelBorder">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-2xl"></div>
            
            <h2 class="text-2xl font-semibold mb-6 flex items-center gap-2">
                <span class="bg-emerald-500/10 text-emerald-400 p-2 rounded-lg text-sm">01</span>
                Nova Solicitação de Crédito
            </h2>
            
            <form id="form-analise" class="space-y-6">
                <x-campo-texto
                    name="nome"
                    label="Nome Completo"
                    placeholder="Digite o nome completo do proponente"
                    required
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-campo-texto
                        name="cpf"
                        label="CPF"
                        placeholder="000.000.000-00"
                        inputmode="numeric"
                        maxlength="14"
                        required
                    />

                    <x-campo-texto
                        name="renda_mensal"
                        label="Renda Mensal (R$)"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Ex: 3500.00"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-campo-selecao name="tipo_credito" label="Tipo de Crédito" required>
                        <option value="" disabled selected>Selecione uma opção</option>
                        <option value="pessoal">Crédito Pessoal</option>
                        <option value="imobiliario">Crédito Imobiliário</option>
                        <option value="automotivo">Crédito Automotivo</option>
                    </x-campo-selecao>

                    <x-campo-texto
                        name="valor_solicitado"
                        label="Valor Requerido (R$)"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Ex: 15000.00"
                        required
                    />
                </div>

                <!-- Botão Enviar -->
                <button type="submit" id="btn-solicitar"
                    class="w-full bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform active:scale-98 shadow-lg shadow-emerald-500/10 flex items-center justify-center gap-2">
                    <span id="txt-solicitar">Solicitar Análise de Crédito</span>
                    <svg id="loading-spinner" class="animate-spin h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </section>

        <!-- Resultados e Contratação -->
        <section class="lg:col-span-5 flex flex-col gap-6">
            
            <!-- Card de Resultado Inicial (Placeholder) -->
            <div id="resultado-vazio" class="glass-panel rounded-3xl p-8 text-center border-dashed border-2 border-panelBorder flex flex-col items-center justify-center py-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-slate-400">Aguardando Solicitação</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-xs">Preencha os dados do formulário ao lado e solicite a análise para simular as condições.</p>
            </div>

            <!-- Card de Resultado da Análise -->
            <div id="resultado-analise" class="glass-panel rounded-3xl p-8 shadow-2xl relative overflow-hidden hidden">
                <div id="status-indicator-badge" class="absolute top-6 right-6">
                    <!-- Badge Aprovado ou Reprovado (Dinâmico) -->
                </div>

                <h3 class="text-xl font-semibold mb-6 flex items-center gap-2">
                    <span class="bg-emerald-500/10 text-emerald-400 p-2 rounded-lg text-sm">02</span>
                    Resultado da Análise
                </h3>

                <!-- Dados da Análise -->
                <div class="space-y-4 divide-y divide-panelBorder">
                    <div class="flex justify-between pt-1">
                        <span class="text-slate-400 text-sm">Proponente</span>
                        <span id="res-nome" class="font-medium text-slate-100">-</span>
                    </div>
                    <div class="flex justify-between pt-4">
                        <span class="text-slate-400 text-sm">CPF</span>
                        <span id="res-cpf" class="font-medium text-slate-100">-</span>
                    </div>
                    <div class="flex justify-between pt-4">
                        <span class="text-slate-400 text-sm">Score de Crédito</span>
                        <span id="res-score" class="font-medium text-slate-100">-</span>
                    </div>
                    <div class="flex justify-between pt-4">
                        <span class="text-slate-400 text-sm">Status da Análise</span>
                        <span id="res-status" class="font-bold">-</span>
                    </div>
                    
                    <!-- Bloco Aprovado -->
                    <div id="dados-aprovado" class="space-y-4 pt-4 hidden">
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Taxa de Juros Aplicada</span>
                            <span id="res-taxa" class="font-medium text-emerald-400">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Parcela Mensal (12x)</span>
                            <span id="res-parcela" class="font-bold text-lg text-emerald-400">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Renda Comprometida</span>
                            <span id="res-comprometimento" class="font-medium text-slate-100">-</span>
                        </div>
                    </div>

                    <!-- Bloco Reprovado -->
                    <div id="dados-reprovado" class="pt-4 hidden">
                        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mt-2">
                            <span class="text-red-400 text-xs block font-semibold uppercase tracking-wider mb-1">Motivo da Recusa</span>
                            <p id="res-motivo" class="text-slate-200 text-sm">-</p>
                        </div>
                    </div>
                </div>

                <!-- Ações para Contratação -->
                <div id="container-contratacao" class="mt-8 pt-6 border-t border-panelBorder hidden">
                    <button id="btn-contratar"
                        class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform active:scale-98 shadow-lg shadow-indigo-500/10 flex items-center justify-center gap-2">
                        <span id="txt-contratar">Confirmar Contratação do Crédito</span>
                        <svg id="loading-spinner-contratar" class="animate-spin h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <p id="txt-ajuda-contratacao" class="text-center text-xs text-slate-500 mt-3">Ao clicar, a simulação será enviada para a fila de processamento da contratação.</p>
                </div>
            </div>

            <!-- Card de Contratação Sucesso/Processando -->
            <div id="card-sucesso-contratacao" class="glass-panel rounded-3xl p-8 border-emerald-500/30 text-center shadow-2xl relative overflow-hidden hidden">
                <div class="h-16 w-16 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-100">Contratação Enviada!</h3>
                <p class="text-sm text-slate-400 mt-2">A simulação de crédito foi encaminhada com sucesso para a nossa fila de processamento em segundo plano.</p>
                <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-3 mt-4 text-xs text-emerald-400 font-mono">
                    Status: PROCESSANDO_CONTRATACAO
                </div>
                <button onclick="window.location.reload()" class="mt-6 text-sm text-emerald-400 hover:text-emerald-300 font-medium transition-all">
                    Solicitar Nova Simulação &rarr;
                </button>
            </div>

        </section>

    </main>

    <x-slot:scripts>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('form-analise');
            const inputCpf = document.getElementById('cpf');
            const btnSolicitar = document.getElementById('btn-solicitar');
            const txtSolicitar = document.getElementById('txt-solicitar');
            const spinner = document.getElementById('loading-spinner');

            const cardVazio = document.getElementById('resultado-vazio');
            const cardResultado = document.getElementById('resultado-analise');
            const badge = document.getElementById('status-indicator-badge');
            const blocoAprovado = document.getElementById('dados-aprovado');
            const blocoReprovado = document.getElementById('dados-reprovado');
            const containerContratacao = document.getElementById('container-contratacao');
            const btnContratar = document.getElementById('btn-contratar');
            const txtContratar = document.getElementById('txt-contratar');
            const ajudaContratacao = document.getElementById('txt-ajuda-contratacao');

            const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
            const banner = criarBanner();

            inputCpf.addEventListener('input', () => {
                inputCpf.value = mascararCpf(inputCpf.value);
            });

            form.addEventListener('submit', async (evento) => {
                evento.preventDefault();
                limparErros();
                carregando(true);

                try {
                    const dados = Object.fromEntries(new FormData(form));
                    dados.cpf = apenasDigitos(dados.cpf);

                    const resposta = await fetch('/api/analise-credito', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                        body: JSON.stringify(dados),
                    });

                    const corpo = await resposta.json().catch(() => ({}));

                    if (resposta.status === 201) {
                        exibirResultado(corpo.data);
                    } else if (resposta.status === 422) {
                        exibirErrosValidacao(corpo);
                    } else {
                        exibirErro(corpo.message ?? 'Não foi possível concluir a análise. Tente novamente.');
                    }
                } catch {
                    exibirErro('Falha de conexão com o servidor. Verifique sua rede e tente novamente.');
                } finally {
                    carregando(false);
                }
            });

            function exibirResultado(analise) {
                const aprovada = analise.status === 'aprovado';

                cardVazio.classList.add('hidden');
                cardResultado.classList.remove('hidden');

                texto('res-nome', analise.nome);
                texto('res-cpf', mascararCpf(analise.cpf));
                texto('res-score', analise.score ?? '—');

                const status = document.getElementById('res-status');
                status.textContent = aprovada ? 'Aprovado' : 'Reprovado';
                status.className = `font-bold ${aprovada ? 'text-emerald-400' : 'text-red-400'}`;
                badge.innerHTML = montarBadge(aprovada);

                blocoAprovado.classList.toggle('hidden', !aprovada);
                blocoReprovado.classList.toggle('hidden', aprovada);
                containerContratacao.classList.toggle('hidden', !aprovada);

                if (aprovada) {
                    texto('res-taxa', `${percentual(analise.taxa_juros, 1)} a.m.`);
                    texto('res-parcela', moeda.format(analise.valor_parcela));
                    texto('res-comprometimento', percentual(analise.comprometimento_renda));
                    prepararSimulacao(analise.id);
                } else {
                    texto('res-motivo', analise.motivo_rejeicao);
                }

                cardResultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // O enunciado pede que a aprovação leve à tela de simulação, e não
            // que a contratação aconteça direto daqui.
            function prepararSimulacao(id) {
                txtContratar.textContent = 'Ver simulação e contratar';
                ajudaContratacao.textContent = 'Você poderá revisar as condições antes de confirmar a contratação.';
                btnContratar.onclick = () => window.location.assign(`/simulacao/${id}`);
            }

            function carregando(ativo) {
                btnSolicitar.disabled = ativo;
                btnSolicitar.classList.toggle('opacity-60', ativo);
                btnSolicitar.classList.toggle('cursor-not-allowed', ativo);
                spinner.classList.toggle('hidden', !ativo);
                txtSolicitar.textContent = ativo ? 'Consultando o Bureau...' : 'Solicitar Análise de Crédito';
            }

            function exibirErro(mensagem) {
                banner.textContent = mensagem;
                banner.classList.remove('hidden');
            }

            function exibirErrosValidacao(corpo) {
                const campos = corpo.errors ?? {};

                Object.keys(campos).forEach((campo) => {
                    document.getElementById(campo)?.classList.add('ring-2', 'ring-red-500/60');
                });

                const mensagens = Object.values(campos).flat();

                if (mensagens.length === 0) {
                    exibirErro(corpo.message ?? 'Dados inválidos.');
                    return;
                }

                banner.innerHTML = `<ul class="list-disc list-inside space-y-1">${mensagens.map(itemDeLista).join('')}</ul>`;
                banner.classList.remove('hidden');
            }

            function limparErros() {
                banner.classList.add('hidden');
                banner.textContent = '';
                form.querySelectorAll('input, select').forEach((campo) => {
                    campo.classList.remove('ring-2', 'ring-red-500/60');
                });
            }

            function criarBanner() {
                const elemento = document.createElement('div');
                elemento.className = 'hidden bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-sm text-red-400';
                btnSolicitar.parentNode.insertBefore(elemento, btnSolicitar);
                return elemento;
            }

            function montarBadge(aprovada) {
                const cores = aprovada
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                    : 'bg-red-500/10 text-red-400 border-red-500/20';

                return `<span class="text-xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full border ${cores}">${aprovada ? 'Aprovado' : 'Reprovado'}</span>`;
            }

            function itemDeLista(mensagem) {
                const item = document.createElement('li');
                item.textContent = mensagem;
                return item.outerHTML;
            }

            function texto(id, valor) {
                document.getElementById(id).textContent = valor;
            }

            function percentual(valor, casas = 2) {
                const formatado = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: casas,
                    maximumFractionDigits: casas,
                }).format(valor ?? 0);

                return `${formatado}%`;
            }

            function apenasDigitos(valor) {
                return String(valor).replace(/\D/g, '');
            }

            function mascararCpf(valor) {
                return apenasDigitos(valor)
                    .slice(0, 11)
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
        });
    </script>
    </x-slot:scripts>

</x-layout>
