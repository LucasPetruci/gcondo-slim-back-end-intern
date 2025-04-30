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

## Tarefa 2: Melhorar a validação da URL duplicada

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

## Tarefa 3: Adicionar a possibilidade de criar reservas

<!-- Tarefa 3 -->

A funcionalidade de reservas permite que moradores de um condomínio reservem espaços, como salões de festa, vinculados à sua unidade. Toda reserva está diretamente associada a uma **unidade** do condomínio.

Cada reserva deve conter obrigatoriamente os campos:

* **Nome** : identificação da reserva
* **Unidade** : referência à unidade do morador
* **Quantidade de pessoas** : total de pessoas previstas no evento
* **Data** : data da reserva no formato ISO 8601 (`YYYY-MM-DD`)

A seguir, foi criada a migration `reservations` com os campos: `id`, `name`, `unit_id`, `people_quantity`, `date`, `created_at`, `updated_at` e `deleted_at`.

```php
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use App\Helpers\PhinxHelper;

final class Reservation extends AbstractMigration
{
     public function change(): void
    {
        $table = $this->table('reservations')
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('people_quantity', 'integer', ['null' => false])
            ->addColumn('date', 'date', ['null' => false]);

        PhinxHelper::setForeignColumn($table, 'unit_id', 'units');
        PhinxHelper::setDatetimeColumns($table); 

        $table->create();
    }
}

```

Após a migration foi criado o model, Reservation.php:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'name',
        'unit_id',
        'people_quantity',
        'date'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}

```

Após, criei o ReservationService.php

```php
<?php

namespace App\Services;

use App\Models\Reservation;
use App\Http\Error\HttpNotFoundException;
use App\Http\Error\HttpUnprocessableEntityException;
use App\Services\UnitService;

class ReservationService
{
    public function __construct(protected UnitService $unitService) {}

    public function list()
    {
        $reservations = Reservation::with('unit')->get();

        return $reservations;
    }

    public function find(int $id): Reservation
    {
        $reservation = Reservation::with('unit')->find($id);

        if (!$reservation) {
            throw new HttpNotFoundException('Reservation not found');
        }

        return $reservation;
    }

    public function create(array $data): Reservation
    {
        $this->validateReservationData($data);

        $this->validateUnit($data['unit_id']);

        $reservation = Reservation::create([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date']
        ]);

        return $reservation;
    }

    public function update(int $id, array $data): Reservation
    {
        $reservation = $this->find($id);

        $this->validateReservationData($data);
        $this->validateUnit($data['unit_id']);

        $reservation-> fill([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date']
        ]);

        $reservation->save();
      
        return $reservation;
    }

    public function delete(int $id): bool
    {
        $reservation = $this->find($id);
        return $reservation->delete();
    }

    /** @throws HttpNotFoundException */
    private function validateUnit(int $unitId): void
    {
        $this->unitService->find($unitId);
    }


    private function validateReservationData(array $data): void
    {
        if (empty($data['name'])) {
            throw new HttpUnprocessableEntityException('Name is required');
        }

        if (empty($data['unit_id'])) {
            throw new HttpUnprocessableEntityException('Unit ID is required');
        }

        if (!isset($data['people_quantity']) || is_null($data['people_quantity']) ||
        !is_numeric($data['people_quantity']) || $data['people_quantity'] <= 0 ||
        $data['people_quantity'] != (int)$data['people_quantity']) {
            throw new HttpUnprocessableEntityException('People quantity must be a non-negative integer');
        }


        if (empty($data['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            throw new HttpUnprocessableEntityException('Date must be in YYYY-MM-DD format');
        }
      
    }
}

```

A validação do campo `people_quantity` foi implementada seguindo o mesmo padrão usado para `bedroom_count` no `UnitService`, garantindo que apenas inteiros positivos sejam aceitos.

Após criar o Service, criei o Controller e as rotas, essas não serão colocadas aqui porque seguem o mesmo padrão dos outros controller e rotas

## Extra

Como reservation está associado a unidades, adicionei uma verificação ao tentar deletar uma unidade que tenha reserva. Seguindo o mesmo padrão de Condiminio e Unidade.
UnitService.php:

```php
 public function delete(int $id): bool
    {
        $unit = $this->find($id);

        // Check if unit has reservations
        if ($unit->reservations()->count() > 0) {
            throw new HttpBadRequestException('Cannot delete unit with reservations');
        }

        $deleted = $unit->delete();

        return $deleted;
    }

```
