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
