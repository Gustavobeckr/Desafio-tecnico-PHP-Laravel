/**
 * Teste end-to-end da tela de clientes (Fase 7) em navegador real.
 *
 * Pré-requisitos:
 *   ./vendor/bin/sail up -d
 *   ./vendor/bin/sail artisan migrate:fresh --seed   (o teste depende do ClienteSeeder)
 *   npm install && npx playwright install chromium
 *
 * Execução:
 *   npm run test:e2e:clientes
 */
import { chromium } from 'playwright';

const BASE = process.env.APP_URL ?? 'http://localhost';
let falhas = 0;
const checar = (nome, ok, d = '') => { if (!ok) falhas++; console.log(`${ok ? 'OK   ' : 'FALHA'} ${nome}${d ? ' | ' + d : ''}`); };

const nav = await chromium.launch();
const pag = await nav.newPage({ viewport: { width: 1440, height: 1000 } });
const erros = [];
pag.on('pageerror', (e) => erros.push(e.message));
pag.on('console', (m) => { if (m.type() === 'error' && !m.text().includes('Failed to load resource')) erros.push(m.text()); });
pag.on('dialog', (d) => d.accept());

await pag.goto(BASE + '/clientes');
await pag.waitForSelector('#lista tr');

const linhas = () => pag.locator('#lista tr').count();
checar('listagem carregou', (await linhas()) > 0, `${await linhas()} linhas`);
checar('paginação informa o total', (await pag.textContent('#resumo')).includes('cliente(s)'));
checar('anterior desabilitado na 1a página', await pag.locator('#btn-anterior').isDisabled());

// --- busca ---
await pag.fill('#busca', 'Maria Oliveira');
await pag.waitForFunction(() => document.querySelectorAll('#lista tr').length === 1, { timeout: 5000 });
checar('busca filtra', (await linhas()) === 1);
checar('CPF vem mascarado', /\d{3}\.\d{3}\.\d{3}-\d{2}/.test(await pag.textContent('#lista tr:first-child')));

// --- detalhe com histórico ---
await pag.click('#lista tr:first-child [data-acao="ver"]');
await pag.waitForSelector('#modal-detalhe:not(.hidden)');
const analises = await pag.locator('#detalhe-analises > div').count();
checar('histórico de análises no detalhe', analises === 2, `${analises} análises`);
checar('mostra o motivo da reprovação', (await pag.textContent('#detalhe-analises')).includes('Comprometimento'));
await pag.click('#btn-fechar-detalhe');

// --- criação ---
await pag.fill('#busca', '');
await pag.waitForTimeout(500);
await pag.click('#btn-novo');
await pag.waitForSelector('#modal-form:not(.hidden)');
await pag.fill('#form-cliente #nome', 'Cliente E2E');
await pag.fill('#form-cliente #cpf', '11122233396');
await pag.fill('#form-cliente #email', 'e2e@example.com');
await pag.fill('#form-cliente #renda_mensal', '7500');
await pag.click('#btn-salvar');
await pag.waitForSelector('#modal-form', { state: 'hidden' });
checar('feedback de sucesso', (await pag.textContent('#feedback')).includes('cadastrado'));

await pag.fill('#busca', 'Cliente E2E');
await pag.waitForFunction(() => document.querySelectorAll('#lista tr').length === 1, { timeout: 5000 });
checar('cliente novo aparece na lista', (await pag.textContent('#lista tr:first-child')).includes('Cliente E2E'));

// --- validação (422) ---
await pag.click('#btn-novo');
await pag.fill('#form-cliente #nome', 'X');
await pag.fill('#form-cliente #cpf', '123');
// Valores que passam na validação nativa do navegador mas falham no servidor:
// CPF com menos de 11 dígitos e e-mail já cadastrado pelo seeder.
await pag.fill('#form-cliente #email', 'maria.oliveira@example.com');
await pag.fill('#form-cliente #renda_mensal', '100');
await pag.click('#btn-salvar');
await pag.waitForSelector('#erros-form:not(.hidden)');
const textoErro = await pag.textContent('#erros-form');
checar('erros em português', textoErro.includes('CPF') && textoErro.includes('e-mail'), textoErro.replace(/\s+/g, ' ').trim().slice(0, 70));
checar('campo inválido destacado', (await pag.getAttribute('#form-cliente #cpf', 'class')).includes('ring-red-500'));
await pag.click('#btn-cancelar');

// --- edição ---
await pag.click('#lista tr:first-child [data-acao="editar"]');
await pag.waitForSelector('#modal-form:not(.hidden)');
checar('formulário pré-preenchido', (await pag.inputValue('#form-cliente #nome')) === 'Cliente E2E');
await pag.fill('#form-cliente #nome', 'Cliente E2E Editado');
await pag.click('#btn-salvar');
await pag.waitForSelector('#modal-form', { state: 'hidden' });
await pag.waitForFunction(() => document.querySelector('#lista tr')?.textContent.includes('Editado'), { timeout: 5000 });
checar('edição refletida na lista', (await pag.textContent('#lista tr:first-child')).includes('Editado'));

// --- exclusão ---
await pag.click('#lista tr:first-child [data-acao="excluir"]');
await pag.waitForFunction(() => document.getElementById('feedback').textContent.includes('removido'), { timeout: 5000 });
checar('exclusão com confirmação', (await pag.textContent('#feedback')).includes('removido'));
await pag.waitForFunction(() => document.querySelectorAll('#lista tr').length === 0, { timeout: 5000 });
checar('lista fica vazia após excluir', await pag.locator('#vazio').isVisible());

// --- navegação entre telas ---
await pag.goto(BASE + '/');
await pag.click('header a[href="/clientes"]');
await pag.waitForURL(/\/clientes$/);
checar('link no header leva aos clientes', pag.url().endsWith('/clientes'));

checar('nenhum erro de JS', erros.length === 0, erros.join(' | '));

await nav.close();
console.log(falhas === 0 ? '\nTODOS OS CHECKS PASSARAM' : `\n${falhas} FALHA(S)`);
process.exit(falhas ? 1 : 0);
