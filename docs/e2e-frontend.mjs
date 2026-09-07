/**
 * Teste end-to-end do frontend (Fases 3 e 4) em navegador real.
 *
 * Pré-requisitos:
 *   ./vendor/bin/sail up -d
 *   npm install                       (playwright está em devDependencies)
 *   npx playwright install chromium   (baixa o navegador, ~150 MB, só na 1a vez)
 *
 * Execução:
 *   node docs/e2e-frontend.mjs
 */
import { chromium } from 'playwright';

// Sail sobe na porta 80; com `artisan serve` use APP_URL=http://localhost:8000
const BASE = process.env.APP_URL ?? 'http://localhost';
let falhas = 0;

const checar = (nome, ok, detalhe = '') => {
    if (!ok) falhas++;
    console.log(`${ok ? 'OK   ' : 'FALHA'} ${nome}${detalhe ? ' | ' + detalhe : ''}`);
};

const navegador = await chromium.launch();
const ctx = await navegador.newContext();
const pagina = await ctx.newPage();

const erros = [];
pagina.on('pageerror', (e) => erros.push(e.message));
// 'Failed to load resource' é o log padrão do browser para qualquer resposta
// não-2xx do fetch — esperado nos cenários de 503 e 422, não é erro de JS.
pagina.on('console', (m) => {
    if (m.type() === 'error' && !m.text().includes('Failed to load resource')) erros.push(m.text());
});

const preencher = async (cpf, renda, valor) => {
    await pagina.goto(BASE + '/');
    await pagina.fill('#nome', 'João da Silva');
    await pagina.fill('#cpf', cpf);
    await pagina.fill('#renda_mensal', String(renda));
    await pagina.selectOption('#tipo_credito', 'pessoal');
    await pagina.fill('#valor_solicitado', String(valor));
    await pagina.click('#btn-solicitar');
};

// --- 1. Aprovado com score alto ---
await preencher('12345678903', 8000, 10000);
await pagina.waitForSelector('#resultado-analise:not(.hidden)', { timeout: 10000 });

checar('URL não virou query string (preventDefault)', !pagina.url().includes('?'), pagina.url());
checar('placeholder escondido', await pagina.locator('#resultado-vazio').isHidden());
checar('status = Aprovado', (await pagina.textContent('#res-status')) === 'Aprovado');
checar('score exibido', (await pagina.textContent('#res-score')) === '850');
checar('CPF mascarado', (await pagina.textContent('#res-cpf')) === '123.456.789-03');
checar('taxa formatada', (await pagina.textContent('#res-taxa')) === '2,9% a.m.');
checar('parcela em BRL', (await pagina.textContent('#res-parcela')).replace(/ /g, ' ') === 'R$ 1.123,33');
checar('comprometimento', (await pagina.textContent('#res-comprometimento')) === '14,04%');
checar('bloco reprovado escondido', await pagina.locator('#dados-reprovado').isHidden());
checar('badge renderizado', (await pagina.textContent('#status-indicator-badge')).trim() === 'Aprovado');
checar('botão de contratação visível', await pagina.locator('#container-contratacao').isVisible());
checar('botão leva à simulação', (await pagina.textContent('#txt-contratar')) === 'Ver simulação e contratar');

// --- 2. Navegação para /simulacao/{id} ---
await pagina.click('#btn-contratar');
await pagina.waitForURL(/\/simulacao\/\d+$/, { timeout: 10000 });
checar('navegou para a simulação', /\/simulacao\/\d+$/.test(pagina.url()), pagina.url());

// --- 3. Contratação ---
await pagina.click('#btn-confirmar');
await pagina.waitForSelector('#modal-sucesso:not(.hidden)', { timeout: 10000 });
checar('modal de sucesso abriu', await pagina.locator('#modal-sucesso').isVisible());
// Com QUEUE_CONNECTION=database e sem worker rodando, o status para em
// processando_contratacao; com sync (ou worker no ar) chega a contratado.
const statusModal = (await pagina.textContent('#modal-status')).trim();
checar('status no modal', /^Status: (PROCESSANDO_CONTRATACAO|CONTRATADO)$/.test(statusModal), statusModal);
checar('título coerente com o status', (await pagina.textContent('#modal-titulo')).includes(
    statusModal.includes('PROCESSANDO') ? 'processamento' : 'realizada'));
checar('botão desabilitado após sucesso', await pagina.locator('#btn-confirmar').isDisabled());

// --- 4. Reprovado ---
await preencher('12345678901', 8000, 5000);
await pagina.waitForSelector('#dados-reprovado:not(.hidden)', { timeout: 10000 });
checar('status = Reprovado', (await pagina.textContent('#res-status')) === 'Reprovado');
checar('motivo exibido', (await pagina.textContent('#res-motivo')) === 'Score de crédito muito baixo');
checar('bloco aprovado escondido', await pagina.locator('#dados-aprovado').isHidden());
checar('sem botão de contratação', await pagina.locator('#container-contratacao').isHidden());

// --- 5. Bureau fora do ar (503) ---
await preencher('12345678904', 8000, 5000);
await pagina.waitForFunction(() => {
    const b = [...document.querySelectorAll('#form-analise div')].find((d) => d.className.includes('bg-red-500/10'));
    return b && !b.classList.contains('hidden');
}, { timeout: 10000 });
const msg503 = await pagina.evaluate(() =>
    [...document.querySelectorAll('#form-analise div')].find((d) => d.className.includes('bg-red-500/10')).textContent);
checar('503 vira mensagem amigável', msg503.includes('Bureau'), msg503.trim().slice(0, 60));
checar('botão reabilitado após erro', await pagina.locator('#btn-solicitar').isEnabled());

// --- 6. Validação (422) ---
await pagina.goto(BASE + '/');
await pagina.fill('#nome', 'X');
await pagina.fill('#cpf', '123');
await pagina.fill('#renda_mensal', '5000');
await pagina.selectOption('#tipo_credito', 'pessoal');
await pagina.fill('#valor_solicitado', '1000');
await pagina.click('#btn-solicitar');
await pagina.waitForFunction(() => document.getElementById('cpf').className.includes('ring-red-500'), { timeout: 10000 });
checar('campo inválido destacado', (await pagina.getAttribute('#cpf', 'class')).includes('ring-red-500'));

checar('nenhum erro de JS no console', erros.length === 0, erros.join(' | '));

await navegador.close();
console.log(falhas === 0 ? '\nTODOS OS CHECKS PASSARAM' : `\n${falhas} FALHA(S)`);
process.exit(falhas ? 1 : 0);
