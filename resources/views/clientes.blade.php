<x-layout titulo="Clientes — Coop0156">

    <x-slot:acao>
        <a href="/" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Nova Análise
        </a>
    </x-slot:acao>

    <main class="flex-grow max-w-6xl mx-auto px-4 py-12 w-full">

        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-semibold flex items-center gap-2">
                    <span class="bg-emerald-500/10 text-emerald-400 p-2 rounded-lg text-sm">01</span>
                    Clientes
                </h2>
                <p class="text-sm text-slate-500 mt-2">Cadastro consumindo <span class="font-mono text-slate-400">/api/clientes</span>.</p>
            </div>
            <button id="btn-novo"
                class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg shadow-emerald-500/10 whitespace-nowrap">
                Novo cliente
            </button>
        </div>

        <div class="glass-panel rounded-3xl p-6 mb-6">
            <label for="busca" class="sr-only">Buscar</label>
            <input type="search" id="busca" placeholder="Buscar por nome, CPF ou e-mail"
                class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
        </div>

        <div id="feedback" class="hidden rounded-xl p-4 mb-6 text-sm"></div>

        <div class="glass-panel rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-400 border-b border-panelBorder">
                        <tr>
                            <th class="px-6 py-4 font-medium">Nome</th>
                            <th class="px-6 py-4 font-medium">CPF</th>
                            <th class="px-6 py-4 font-medium">E-mail</th>
                            <th class="px-6 py-4 font-medium text-right">Renda</th>
                            <th class="px-6 py-4 font-medium text-center">Análises</th>
                            <th class="px-6 py-4 font-medium text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lista" class="divide-y divide-panelBorder/60"></tbody>
                </table>
            </div>

            <div id="vazio" class="hidden py-16 text-center text-slate-500 text-sm">Nenhum cliente encontrado.</div>

            <div id="paginacao" class="flex items-center justify-between gap-4 px-6 py-4 border-t border-panelBorder">
                <span id="resumo" class="text-xs text-slate-500"></span>
                <div class="flex gap-2">
                    <button id="btn-anterior" class="px-4 py-2 rounded-lg border border-panelBorder text-slate-400 hover:text-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-sm transition-all">Anterior</button>
                    <button id="btn-proxima" class="px-4 py-2 rounded-lg border border-panelBorder text-slate-400 hover:text-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-sm transition-all">Próxima</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de cadastro/edição -->
    <div id="modal-form" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
        <div class="glass-panel rounded-3xl p-8 max-w-lg w-full">
            <h3 id="modal-form-titulo" class="text-xl font-semibold text-white mb-6">Novo cliente</h3>

            <form id="form-cliente" class="space-y-5">
                <x-campo-texto name="nome" label="Nome Completo" placeholder="Nome completo" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-campo-texto name="cpf" label="CPF" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" required />
                    <x-campo-texto name="telefone" label="Telefone" placeholder="51 99999-0000" />
                </div>

                <x-campo-texto name="email" label="E-mail" type="email" placeholder="email@exemplo.com" required />
                <x-campo-texto name="renda_mensal" label="Renda Mensal (R$)" type="number" step="0.01" min="0" placeholder="Ex: 3500.00" required />

                <div id="erros-form" class="hidden bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button type="button" id="btn-cancelar" class="flex-1 px-6 py-3 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 transition-all text-sm font-medium">Cancelar</button>
                    <button type="submit" id="btn-salvar" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-all">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de detalhe -->
    <div id="modal-detalhe" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
        <div class="glass-panel rounded-3xl p-8 max-w-2xl w-full max-h-[85vh] overflow-y-auto">
            <h3 id="detalhe-nome" class="text-xl font-semibold text-white mb-1"></h3>
            <p id="detalhe-sub" class="text-sm text-slate-500 mb-6"></p>
            <h4 class="text-sm font-medium text-slate-400 mb-3">Histórico de análises</h4>
            <div id="detalhe-analises" class="space-y-3"></div>
            <button id="btn-fechar-detalhe" class="mt-8 w-full px-6 py-3 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 transition-all text-sm font-medium">Fechar</button>
        </div>
    </div>

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lista = document.getElementById('lista');
                const vazio = document.getElementById('vazio');
                const resumo = document.getElementById('resumo');
                const busca = document.getElementById('busca');
                const feedback = document.getElementById('feedback');
                const btnAnterior = document.getElementById('btn-anterior');
                const btnProxima = document.getElementById('btn-proxima');

                const modalForm = document.getElementById('modal-form');
                const modalFormTitulo = document.getElementById('modal-form-titulo');
                const formCliente = document.getElementById('form-cliente');
                const errosForm = document.getElementById('erros-form');
                const btnSalvar = document.getElementById('btn-salvar');

                const modalDetalhe = document.getElementById('modal-detalhe');

                const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
                let pagina = 1;
                let emEdicao = null;
                let debounce;

                carregar();

                busca.addEventListener('input', () => {
                    clearTimeout(debounce);
                    debounce = setTimeout(() => { pagina = 1; carregar(); }, 300);
                });

                btnAnterior.addEventListener('click', () => { pagina--; carregar(); });
                btnProxima.addEventListener('click', () => { pagina++; carregar(); });

                document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
                document.getElementById('btn-cancelar').addEventListener('click', fecharForm);
                document.getElementById('btn-fechar-detalhe').addEventListener('click', () => modalDetalhe.classList.add('hidden'));

                formCliente.addEventListener('submit', salvar);

                async function carregar() {
                    const url = `/api/clientes?page=${pagina}&busca=${encodeURIComponent(busca.value)}`;
                    const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
                    const corpo = await resposta.json();

                    lista.innerHTML = corpo.data.map(linha).join('');
                    vazio.classList.toggle('hidden', corpo.data.length > 0);

                    const { current_page: atual, last_page: ultima, total } = corpo.meta;
                    resumo.textContent = total === 0 ? '' : `${total} cliente(s) — página ${atual} de ${ultima}`;
                    btnAnterior.disabled = atual <= 1;
                    btnProxima.disabled = atual >= ultima;

                    lista.querySelectorAll('[data-acao]').forEach((botao) => {
                        botao.addEventListener('click', () => {
                            const id = Number(botao.dataset.id);
                            if (botao.dataset.acao === 'editar') abrirForm(corpo.data.find((c) => c.id === id));
                            if (botao.dataset.acao === 'excluir') excluir(id);
                            if (botao.dataset.acao === 'ver') detalhar(id);
                        });
                    });
                }

                function linha(cliente) {
                    return `<tr class="hover:bg-slate-950/30 transition-colors">
                        <td class="px-6 py-4 text-slate-100">${escapar(cliente.nome)}</td>
                        <td class="px-6 py-4 font-mono text-slate-300 whitespace-nowrap">${mascararCpf(cliente.cpf)}</td>
                        <td class="px-6 py-4 text-slate-400">${escapar(cliente.email ?? '—')}</td>
                        <td class="px-6 py-4 text-right text-slate-200">${moeda.format(cliente.renda_mensal)}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block min-w-[2rem] px-2 py-0.5 rounded-full text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">${cliente.analises_count ?? 0}</span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button data-acao="ver" data-id="${cliente.id}" class="text-slate-400 hover:text-emerald-400 transition-colors text-xs px-2">Detalhes</button>
                            <button data-acao="editar" data-id="${cliente.id}" class="text-slate-400 hover:text-blue-400 transition-colors text-xs px-2">Editar</button>
                            <button data-acao="excluir" data-id="${cliente.id}" class="text-slate-400 hover:text-red-400 transition-colors text-xs px-2">Excluir</button>
                        </td>
                    </tr>`;
                }

                function abrirForm(cliente = null) {
                    emEdicao = cliente;
                    modalFormTitulo.textContent = cliente ? 'Editar cliente' : 'Novo cliente';
                    formCliente.reset();
                    limparErros();

                    if (cliente) {
                        formCliente.nome.value = cliente.nome;
                        formCliente.cpf.value = mascararCpf(cliente.cpf);
                        formCliente.email.value = cliente.email ?? '';
                        formCliente.telefone.value = cliente.telefone ?? '';
                        formCliente.renda_mensal.value = cliente.renda_mensal;
                    }

                    modalForm.classList.remove('hidden');
                    formCliente.nome.focus();
                }

                function fecharForm() {
                    modalForm.classList.add('hidden');
                    emEdicao = null;
                }

                async function salvar(evento) {
                    evento.preventDefault();
                    limparErros();
                    btnSalvar.disabled = true;

                    try {
                        const dados = Object.fromEntries(new FormData(formCliente));
                        dados.cpf = apenasDigitos(dados.cpf);

                        const resposta = await fetch(emEdicao ? `/api/clientes/${emEdicao.id}` : '/api/clientes', {
                            method: emEdicao ? 'PUT' : 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify(dados),
                        });

                        const corpo = await resposta.json().catch(() => ({}));

                        if (resposta.ok) {
                            fecharForm();
                            avisar(emEdicao ? 'Cliente atualizado.' : 'Cliente cadastrado.', 'sucesso');
                            carregar();
                            return;
                        }

                        if (resposta.status === 422) {
                            mostrarErros(corpo);
                            return;
                        }

                        mostrarErros({ message: corpo.message ?? 'Não foi possível salvar.' });
                    } catch {
                        mostrarErros({ message: 'Falha de conexão com o servidor.' });
                    } finally {
                        btnSalvar.disabled = false;
                    }
                }

                async function excluir(id) {
                    if (! confirm('Remover este cliente? As análises dele deixarão de ficar vinculadas.')) return;

                    const resposta = await fetch(`/api/clientes/${id}`, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                    });

                    if (resposta.status === 204) {
                        avisar('Cliente removido.', 'sucesso');
                        carregar();
                    } else {
                        avisar('Não foi possível remover o cliente.', 'erro');
                    }
                }

                async function detalhar(id) {
                    const resposta = await fetch(`/api/clientes/${id}`, { headers: { Accept: 'application/json' } });
                    const { data } = await resposta.json();

                    document.getElementById('detalhe-nome').textContent = data.nome;
                    document.getElementById('detalhe-sub').textContent =
                        `${mascararCpf(data.cpf)} — renda de ${moeda.format(data.renda_mensal)}`;

                    const container = document.getElementById('detalhe-analises');
                    container.innerHTML = data.analises.length === 0
                        ? '<p class="text-sm text-slate-500">Nenhuma análise registrada.</p>'
                        : data.analises.map(cartaoAnalise).join('');

                    modalDetalhe.classList.remove('hidden');
                }

                function cartaoAnalise(analise) {
                    const cores = {
                        aprovado: 'text-emerald-400 border-emerald-500/20 bg-emerald-500/5',
                        reprovado: 'text-red-400 border-red-500/20 bg-red-500/5',
                        contratado: 'text-blue-400 border-blue-500/20 bg-blue-500/5',
                    };
                    const cor = cores[analise.status] ?? 'text-slate-400 border-panelBorder bg-slate-950/30';
                    const detalhe = analise.status === 'reprovado'
                        ? escapar(analise.motivo_rejeicao ?? '')
                        : `${moeda.format(analise.valor_parcela ?? 0)} em ${analise.parcelas}x a ${(analise.taxa_juros ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 1 })}% a.m.`;

                    return `<div class="rounded-xl border p-4 ${cor}">
                        <div class="flex justify-between items-center gap-4">
                            <span class="text-xs font-semibold uppercase tracking-wider">${analise.status.replace(/_/g, ' ')}</span>
                            <span class="text-sm text-slate-300">${moeda.format(analise.valor_solicitado)}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">${detalhe}</p>
                    </div>`;
                }

                function mostrarErros(corpo) {
                    const mensagens = Object.values(corpo.errors ?? {}).flat();
                    Object.keys(corpo.errors ?? {}).forEach((campo) => {
                        formCliente.querySelector(`#${campo}`)?.classList.add('ring-2', 'ring-red-500/60');
                    });

                    errosForm.innerHTML = mensagens.length
                        ? `<ul class="list-disc list-inside space-y-1">${mensagens.map(item).join('')}</ul>`
                        : escapar(corpo.message ?? 'Dados inválidos.');
                    errosForm.classList.remove('hidden');
                }

                function limparErros() {
                    errosForm.classList.add('hidden');
                    errosForm.textContent = '';
                    formCliente.querySelectorAll('input').forEach((c) => c.classList.remove('ring-2', 'ring-red-500/60'));
                }

                function avisar(mensagem, tipo) {
                    feedback.textContent = mensagem;
                    feedback.className = tipo === 'sucesso'
                        ? 'rounded-xl p-4 mb-6 text-sm bg-emerald-500/10 border border-emerald-500/20 text-emerald-400'
                        : 'rounded-xl p-4 mb-6 text-sm bg-red-500/10 border border-red-500/20 text-red-400';
                    setTimeout(() => feedback.classList.add('hidden'), 4000);
                }

                function item(mensagem) {
                    const li = document.createElement('li');
                    li.textContent = mensagem;
                    return li.outerHTML;
                }

                function escapar(texto) {
                    const div = document.createElement('div');
                    div.textContent = texto ?? '';
                    return div.innerHTML;
                }

                function apenasDigitos(valor) {
                    return String(valor).replace(/\D/g, '');
                }

                function mascararCpf(valor) {
                    return apenasDigitos(valor).slice(0, 11)
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                }
            });
        </script>
    </x-slot:scripts>

</x-layout>
