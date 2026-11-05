<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminConfigurationTest extends TestCase
{
    public function test_admin_options_have_working_defaults(): void
    {
        $this->assertSame('Administrador', config('template.admin.admin_profile_name'));
        $this->assertSame('Usuário', config('template.admin.default_profile_name'));
        $this->assertSame(3, config('template.admin.email_throttle'));
        $this->assertSame(10, config('template.admin.email_throttle_minutes'));
        $this->assertSame(';', config('template.admin.csv_delimiter'));
    }
}
