<?php

declare(strict_types=1);

use App\Helpers\PhinxHelper;
use Phinx\Migration\AbstractMigration;

final class Location extends AbstractMigration
{
   
    public function change(): void
    {
        $table = $this->table('locations')
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('max_people', 'integer')
            ->addColumn('square_meters', 'float', ['null' => true]);

        PhinxHelper::setForeignColumn($table, 'condominium_id', 'condominiums');
        PhinxHelper::setDatetimeColumns($table);
        $table->create();

    }
}
