<?php

namespace Tests\Feature\History;

use App\Models\ChangeHistory;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HistoryCoverageTest extends TestCase
{
    private const UNRECORDED_WRITES = '/DB::table\(|(->|::)(insert|upsert|increment|decrement|truncate|withoutEvents)\(|Quietly\(/';

    public function test_every_entity_is_recorded(): void
    {
        $models = collect(File::files(app_path('Models')))
            ->map(fn ($file) => 'App\\Models\\'.$file->getBasename('.php'))
            ->filter(fn (string $class) => is_subclass_of($class, Model::class))
            ->reject(fn (string $class) => $class === ChangeHistory::class);

        $this->assertNotEmpty($models);

        foreach ($models as $model) {
            $this->assertContains(RecordsChanges::class, class_uses_recursive($model), "{$model} não usa RecordsChanges.");
        }
    }

    public function test_the_application_does_not_write_around_the_history(): void
    {
        $files = collect([app_path(), database_path('seeders'), base_path('routes')])
            ->flatMap(fn (string $directory) => File::allFiles($directory))
            ->reject(fn ($file) => $file->getFilename() === 'ChangeRecorder.php')
            ->filter(fn ($file) => preg_match(self::UNRECORDED_WRITES, $file->getContents()))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values();

        $this->assertSame([], $files->all());
    }
}
