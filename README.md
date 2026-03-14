# Multi Gateway Payment API

API de pagamentos que orquestra múltiplos gateways (com fallback por prioridade), suporta idempotência para compra e reembolso e executa reconciliação financeira (job) após cada operação.

## Stack

- Laravel 12 + PHP 8.2+ (Docker usa `php:8.4-cli`)
- MySQL 8
- Auth via Laravel Sanctum (Bearer token)
- Gateways mock (container `matheusprotzen/gateways-mock`)
- Testes: Pest/PHPUnit

## Requisitos
- Docker + Docker Compose

## Configuração do ambiente

O projeto possui um arquivo de exemplo em `.env.example`.<br>
Copie e cole o arquivo `.env.example` e renomei o arquivo copiado para `.env`<br>
ou use um dos comandos abaixo.

- PowerShell (Windows):

    ```powershell
    Copy-Item .env.example .env
    ```

- Git Bash / Linux / WSL:

    ```bash
    cp .env.example .env
    ```

Variáveis importantes (já presentes no `.env.example`):

- `DB_*` (MySQL)
- `GATEWAY1_*` e `GATEWAY2_*` (integração com o mock)
- `SANCTUM_TOKEN_EXPIRATION` (minutos)

## Como executar

1. Após o `.env` criado conforme a seção acima.
2. Suba os containers:

    ```bash
    docker compose up -d --build
    ```

O Compose executa automaticamente `composer install`, `php artisan migrate --force` e inicia a API em `http://localhost:8000`.

Para ver logs:

```bash
docker compose logs -f app
```

Para confirmar rapidamente que as dependências foram instaladas dentro do container:

```bash
docker compose exec app sh -lc "test -f vendor/autoload.php && echo OK"
```

Para parar:

```bash
docker compose down
```

## Autenticação

- `POST /api/login` retorna um token Sanctum.
- Para acessar as rotas de `/api/v1/*`, envie `Authorization: Bearer <TOKEN>`.

Observação: a expiração do token é controlada por `SANCTUM_TOKEN_EXPIRATION` (padrão: 5 minutos).

### Usuário admin padrão

Durante as migrations existe uma inserção automática de um usuário ADMIN:

- email: `admin_master@gmail.com`
- senha: `12345678`

## Papéis e permissões

Roles disponíveis: `ADMIN`, `MANAGER`, `FINANCE`, `USER`.

Permissões (resumo conforme gates em `AuthorizationServiceProvider`):

- Users: `ADMIN` e `MANAGER` (com restrições para `MANAGER` editar/excluir `ADMIN`)
- Products: `ADMIN`, `MANAGER`, `FINANCE`
- Clients: apenas `ADMIN`
- Gateways: apenas `ADMIN`
- Transactions (listar/ver): apenas `ADMIN`
- Refund: `ADMIN` e `FINANCE`

## Endpoints

Documentação detalhada (payloads e exemplos):

- [Documentação da API](https://documenter.getpostman.com/view/18453345/2sBXigMDbY)

Endpoints principais:

- `POST /api/login`
- `POST /api/purchase` (não requer autenticação)
- `GET /api/v1/transactions` (auth)
- `POST /api/v1/transactions/{id}/refund` (auth)

## Idempotência

Algumas operações aceitam o header `Idempotency-Key`.

- Compra (`POST /api/purchase`):
    - mesma chave + mesmo payload: retorna transação já processada (HTTP 200)
    - mesma chave + payload diferente: erro (HTTP 422)
- Reembolso (`POST /api/v1/transactions/{id}/refund`):
    - mesma chave + mesma transação: retorna o resultado já processado
    - mesma chave + outra transação: erro (HTTP 422)

## Rate limiting

- `login`: 5 req/min por IP
- `api`: 480 req/min por usuário (ou IP)

## Testes

- Local:

    ```bash
    composer test
    ```

- Com Docker:

    ```bash
    docker compose exec app php artisan test
    ```
