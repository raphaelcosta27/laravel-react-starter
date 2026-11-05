<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    /**
     * O WAMP usa MyISAM como padrão, e esse motor não comporta os índices de
     * e-mail em utf8mb4. O motor precisa ser definido pela aplicação.
     */
    public function test_mysql_connection_uses_innodb(): void
    {
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
    }
}
