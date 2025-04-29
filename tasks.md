# Ticket #456

<!-- Contexto -->

Este ticket propõe ajustes e melhorias na API do projeto Gcondo Slim, desenvolvida em PHP com o Slim Framework 4. As atividades envolvem a correção da validação do campo URL no cadastro de Condomínios, a melhoria nas mensagens de erro para evitar exposição de informações sensíveis, e a criação de um novo módulo de Reservas, com possibilidade de expansão para gerenciamento de Locais.

<!-- Tarefas -->

1. Tornar o campo `url` opcional no cadastro de Condomínios.
2. Melhorar a validação de URL para tratar erros de duplicidade com mensagens amigáveis.
3. Criar reservas dos salões de festa.

<!-- Tarefa 1 -->

## Tarefa 1: Tornar o campo `url` opcional no cadastro de Condomínios

No `CondominiumService`, dentro da função `validateCondominiumData`, existia uma validação que obrigava o preenchimento do campo `url`:

```php
if (empty($data['url'])) {
throw new HttpUnprocessableEntityException('URL is required');
}

```

Essa verificação foi removida para atender à nova regra de negócio, permitindo que o campo `url` seja opcional.
Após a alteração, foi possível criar um condomínio sem informar a URL. A resposta da API foi:

```json
{
	"statusCode": 201,
	"data": {
		"condominium": {
			"name": "Residencial Porto Seguro",
			"zip_code": "12345678",
			"url": "",
			"updated_at": "2025-04-29T04:14:03.000000Z",
			"created_at": "2025-04-29T04:14:03.000000Z",
			"id": 2
		}
	}
}
```

Além disso, foi criada uma nova migration no banco de dados para permitir passar a url como null

```php-template
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeUrlNullableInCondominios extends AbstractMigration
{
    public function change(): void
    {
        $this->table('condominiums')
        ->changeColumn('url', 'string', ['null' => true, 'limit' => 255])
        ->update();
    }
}

```

Com essa alteração, o sistema passou a aceitar `null` no campo `url`, conforme validado na seguinte criação:

```json
{
	"statusCode": 201,
	"data": {
		"condominium": {
			"name": "Residencial Porto Seguro",
			"zip_code": "12345678",
			"url": null,
			"updated_at": "2025-04-29T04:29:45.000000Z",
			"created_at": "2025-04-29T04:29:45.000000Z",
			"id": 3
		}
	}
}
```

Além disso, como não foi especificado o sistema também permite a criação como String vazia:

```json
{
	"statusCode": 201,
	"data": {
		"condominium": {
			"name": "Residencial Porto Seguro",
			"zip_code": "12345678",
			"url": "",
			"updated_at": "2025-04-29T04:39:33.000000Z",
			"created_at": "2025-04-29T04:39:33.000000Z",
			"id": 7
		}
	}
}
```

<!-- Tarefa 2 -->

## Tarefa 2: Melhorar a validação da  `url` duplicada

Ao tentar criar um novo condomínio com uma URL já existente, o sistema retornava o seguinte erro:

```json
{
	"statusCode": 500,
	"error": {
		"type": "SERVER_ERROR",
		"description": "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'https://www.portoseguro.br' for key 'condominiums.url' (Connection: default, SQL: insert into `condominiums` (`name`, `zip_code`, `url`, `updated_at`, `created_at`) values (Residencial Porto Seguro, 12345678, https://www.portoseguro.br, 2025-04-29 16:26:04, 2025-04-29 16:26:04))"
	}
}
```

Esse comportamento expunha detalhes internos do banco de dados (falha de segurança) e não apresentava uma mensagem amigável ao usuário final.

Solução Implementada:
Dentro da classe `CondominiumService.php`, no método `validateCondominiumData`, foi adicionada uma nova validação para impedir a duplicidade da URL antes de tentar inserir no banco:

```php
  //Validate duplicated URL - error handler
        if (Condominium::where('url', $data['url'])->exists()) {
            throw new HttpUnprocessableEntityException('URL already exists');
        }
```

Essa validação verifica previamente se já existe um condomínio cadastrado com a mesma URL e, se existir, retorna uma exceção amigável com código HTTP `422`.

Resultado após a correção
Create condominium - Post:

```json
{
	"statusCode": 422,
	"error": {
		"type": "VALIDATION_ERROR",
		"description": "URL already exists"
	}
}
```

Update condominium - Put:

```json
{
	"statusCode": 422,
	"error": {
		"type": "VALIDATION_ERROR",
		"description": "URL already exists"
	}
}
```

<!-- Tarefa 3 -->
