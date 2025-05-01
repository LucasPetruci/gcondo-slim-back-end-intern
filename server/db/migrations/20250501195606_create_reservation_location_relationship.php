<?php

declare(strict_types=1);

use App\Helpers\PhinxHelper;
use Phinx\Migration\AbstractMigration;

final class CreateReservationLocationRelationship extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('reservations');
        
        PhinxHelper::setForeignColumn($table, 'location_id', 'locations', true);
        
        $table->update();
    }
}