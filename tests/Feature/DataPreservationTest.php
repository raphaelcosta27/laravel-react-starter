<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class DataPreservationTest extends TestCase
{
    private const FORBIDDEN = '/forceDelete|->detach\(|->sync\(|->truncate\(|cascadeOnDelete|onDelete\(\'cascade\'\)/';

    public function test_application_code_never_deletes_data_physically(): void
    {
        $files = [...File::allFiles(app_path()), ...File::allFiles(database_path())];

        $offenders = collect($files)
            ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
            ->filter(fn (SplFileInfo $file) => preg_match(self::FORBIDDEN, $file->getContents()) === 1)
            ->map(fn (SplFileInfo $file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders);
    }
}
