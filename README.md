# Coop0156 - Análise de Crédito

Solução para o desafio técnico de PHP/Laravel. O enunciado original está preservado em
[DESAFIO.md](DESAFIO.md).

Plataforma de cooperativa para cadastro de clientes, análise de crédito consultando um Bureau
externo e contratação do crédito aprovado.

---

## Como executar

O ambiente de avaliação é o **Laravel Sail**.

```bash
# 1. Dependências (não requer PHP local)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install

# 2. Ambiente
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed     # --seed popula uma base de demonstração

# 3. Acesse http://localhost
```

> **Sobre a imagem do Composer.** O enunciado usa `laravelsail/php83-composer` com
> `--ignore-platform-reqs`, mas o `composer.lock` traz 20 pacotes do Symfony que exigem
> `php >=8.4.1` e usam _property hooks_ (sintaxe do PHP 8.4). Com a imagem 8.3 a instalação até
> conclui, porém o script final (`artisan package:discover`) quebra com
> `syntax error, unexpected token "{"` ao tentar executar esse código.
>
> Usando a imagem **8.4**, os requisitos de plataforma passam a ser atendidos de verdade
> (`composer check-platform-reqs` não acusa nada) e a flag deixa de ser necessária - ela só
> mascarava a incompatibilidade.

> **Atenção ao `APP_URL`.** O mock do Bureau é servido pela própria aplicação e
> `SCORE_BUREAU_API_URL` deriva do `APP_URL`. O `.env.example` já vem com `http://localhost`
> (porta do Sail). Se optar por `php artisan serve`, troque para `http://localhost:8000`, senão
> toda análise falha com 503.

**Contratação assíncrona** - a contratação é processada em fila. Deixe um worker rodando:

```bash
./vendor/bin/sail artisan queue:work
```

Sem ele a análise para em `processando_contratacao` (comportamento esperado).

## Testes

```bash
./vendor/bin/sail artisan test        # 72 testes
npm install && npx playwright install chromium
npm run test:e2e:all                  # 42 checks em navegador real
```

A suíte PHP usa `Http::fake()` e SQLite em memória - não depende de rede nem do banco de
desenvolvimento. Os testes E2E (Playwright/Chromium) sobem um navegador contra a aplicação real
e cobrem o JavaScript, que a suíte PHP não alcança. O roteiro de clientes depende do seeder (`sail artisan migrate:fresh --seed`)

---

## O que foi implementado

| Etapa do enunciado                             | Situação                                                       |
| ---------------------------------------------- | -------------------------------------------------------------- |
| 1. CRUD de clientes                            | completo                                                       |
| 2. Integração com o Bureau e regras de negócio | completo                                                       |
| 3. Tela de simulação e contratação             | completo                                                       |
| 4. Testes automatizados                        | completo (18 cenários pedidos + casos de borda)                |
| ⭐ Filas                                       | implementado                                                   |
| ⭐ Vá além                                     | tela de clientes, layout e componentes Blade, i18n, testes E2E |

### Arquitetura

A regra de negócio não fica no controller. As camadas:

```
Form Request  →  Controller  →  Service  →  Gateway do Bureau
 (validação)     (HTTP)         (orquestra)   (única parte que conhece Http::)
                                     ↓
                              PoliticaCredito
                          (regras puras, sem I/O)
```

- **`app/Services/Bureau/`** - `BureauCreditoGateway` é uma interface (bind no
  `AppServiceProvider`); `HttpBureauCreditoGateway` é a única classe que conhece `Http::`,
  timeouts e o formato da resposta externa. Traduz falha de rede, erro HTTP e payload sem `score`
  numa mesma `BureauIndisponivelException` - o resto do sistema nunca vê um `Response` cru.
- **`app/Services/Credito/`** - `PoliticaCredito` aplica as regras na ordem do enunciado e
  devolve um `ResultadoAnalise`; `SimulacaoParcelamento` calcula os juros simples em 12 parcelas.
  Nenhuma das duas toca banco, HTTP ou `Request`, então são testáveis isoladamente.
- **`bootstrap/app.php`** - centraliza a tradução exceção → HTTP (`404`, `503`, `422`), só em
  rotas de API. Os controllers não têm `try/catch`.

### Resiliência ao Bureau

Os três modos de falha do mock são tratados e devolvem **503** com mensagem amigável, nunca 500:

| CPF termina em | Mock             | Tratamento                             |
| -------------- | ---------------- | -------------------------------------- |
| `4`            | HTTP 500         | `$resposta->failed()`                  |
| `5`            | atraso de 5s     | `ConnectionException` no timeout de 3s |
| `6`            | JSON sem `score` | validação do payload                   |

Em todos, a análise permanece `pendente` no banco - o registro da tentativa é preservado. O log
grava o motivo com o **CPF mascarado** (`123******04`).

### Telas

- `/` - solicitação de análise; aprovada, leva para a simulação
- `/simulacao/{id}` - condições e confirmação da contratação
- `/clientes` - CRUD completo com busca, paginação e histórico de análises por cliente

JavaScript puro, sem build. As views compartilham o layout `<x-layout>` e os componentes
`<x-campo-texto>` / `<x-campo-selecao>`.

---

## Decisões técnicas

**Falha do Bureau devolve 503, não 500 nem 200.** A indisponibilidade de um terceiro não é erro
da aplicação, e também não é um resultado de análise. A análise fica `pendente`, preservando o
registro da tentativa - o passo 6 do enunciado diz "**atualizar** a análise no banco", o que
pressupõe a linha já criada no passo 3.

**Tradução de exceção centralizada.** Em vez de `try/catch` em cada controller, os `render()` de
`bootstrap/app.php` mapeiam exceção de domínio para status HTTP. Um caso novo é uma entrada ali,
não um `catch` espalhado.

**Rota de contratação usa `{analise}`, não `{id}`.** O route model binding do Laravel casa pelo
**nome** do parâmetro; com `{id}` e `AnaliseCredito $analise` o binding não ocorreria e o
container resolveria um model vazio - falha silenciosa. A URL pública não mudou.

**`clientes.email` passou a ser nullable.** O fluxo de análise cadastra o cliente pelo CPF e o
formulário não coleta e-mail. A alternativa seria inventar um endereço sintético. A
obrigatoriedade continua valendo no `POST /api/clientes`, onde faz sentido.

**Deduplicação de análises pendentes.** Repetir o mesmo pedido com o Bureau fora reaproveita a
linha pendente em vez de empilhar uma nova a cada clique (`firstOrCreate` sobre status, tipo,
valor e renda).

**Mensagens de validação em `lang/pt_BR/`.** Traduzir regra genérica no `messages()` de cada
Form Request duplicaria a mesma frase por todo o projeto. Os rótulos de campo seguem a cascata do
Laravel: o que é comum a vários formulários fica no arquivo global, o que é específico fica no
`attributes()` do request.

---

## Documentação adicional

- [DESAFIO.md](DESAFIO.md) - enunciado original
- [docs/Coop0156.postman_collection.json](docs/Coop0156.postman_collection.json) - um request por
  cenário do mock, com os valores esperados na descrição
- [docs/e2e-frontend.mjs](docs/e2e-frontend.mjs) e [docs/e2e-clientes.mjs](docs/e2e-clientes.mjs) - testes e2e para telas web usando playwright
