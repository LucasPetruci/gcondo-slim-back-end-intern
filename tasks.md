# Ticket #456

<!-- Contexto -->

Este ticket propõe ajustes e melhorias na API do projeto Gcondo Slim, desenvolvida em PHP com o Slim Framework 4. As atividades envolvem a correção da validação do campo URL no cadastro de Condomínios, a melhoria nas mensagens de erro para evitar exposição de informações sensíveis, e a criação de um novo módulo de Reservas, com possibilidade de expansão para gerenciamento de Locais.


<!-- Tarefas -->

1. Tornar o campo `url` opcional no cadastro de Condomínios.
2. melhorar a validação de URL para tratar erros de duplicidade com mensagens amigáveis.
3. criar o CRUD de Reservas relacionadas às Unidades.
4. (opcional) criar o CRUD de Locais e relacioná-los às Reservas.

<!-- Tarefa 1 -->

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

<!-- Tarefa 2 -->


<!-- Tarefa 3 -->
