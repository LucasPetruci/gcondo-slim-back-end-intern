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
