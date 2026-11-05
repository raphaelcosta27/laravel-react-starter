<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_cannot_be_changed(): void
    {
        $permission = Permission::factory()->create(['code' => 'gerar_relatorio']);

        $this->expectException(LogicException::class);

        $permission->update(['code' => 'outro_codigo']);
    }

    public function test_code_cannot_be_changed_with_force_fill(): void
    {
        $permission = Permission::factory()->create(['code' => 'gerar_relatorio']);

        $this->expectException(LogicException::class);

        $permission->forceFill(['code' => 'outro_codigo'])->save();
    }

    public function test_title_and_description_can_be_changed(): void
    {
        $permission = Permission::factory()->create(['code' => 'gerar_relatorio']);

        $permission->update(['title' => 'Novo título', 'description' => 'Nova descrição']);

        $this->assertSame('Novo título', $permission->fresh()->title);
        $this->assertSame('gerar_relatorio', $permission->fresh()->code);
    }
}
