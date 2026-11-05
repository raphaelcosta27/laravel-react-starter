<?php

namespace Tests\Concurrency;

use App\Actions\Admin\ChangeUserStatus;
use App\Actions\Admin\DeleteUser;
use App\Actions\CreateUser;
use App\Models\Profile;
use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Prova os bloqueios de R6 com duas conexões reais. A primeira segura uma
 * transação aberta, e a segunda precisa esperar até estourar o tempo limite.
 */
#[Group('mysql')]
class AdministratorLockTest extends TestCase
{
    use DatabaseMigrations;

    private const LOCK_WAIT_TIMEOUT = 1205;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Este teste precisa de MySQL. Use: php vendor/bin/phpunit -c phpunit.mysql.xml');
        }

        config(['database.connections.second' => config('database.connections.mysql')]);

        DB::connection('second')->statement('SET innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        DB::disconnect('second');

        parent::tearDown();
    }

    private function assertBlocked(Closure $query): void
    {
        try {
            DB::connection('second')->transaction($query);
        } catch (QueryException $e) {
            $this->assertSame(self::LOCK_WAIT_TIMEOUT, $e->errorInfo[1]);

            return;
        }

        $this->fail('A segunda conexão não foi bloqueada.');
    }

    private function lockActiveAdministrators(): Closure
    {
        return fn () => User::on('second')
            ->where('profile_id', Profile::administrator()->id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get();
    }

    public function test_second_first_user_waits_for_the_first_one(): void
    {
        $profileId = Profile::administrator()->id;

        DB::beginTransaction();

        app(CreateUser::class)->verified('Ana', 'ana@example.com', 'senha-segura');

        $this->assertBlocked(fn () => Profile::on('second')->whereKey($profileId)->lockForUpdate()->first());

        DB::rollBack();
    }

    public function test_deleting_an_administrator_locks_the_others(): void
    {
        $target = User::factory()->administrator()->create();
        User::factory()->administrator()->create();

        DB::beginTransaction();

        app(DeleteUser::class)->handle($target);

        $this->assertBlocked($this->lockActiveAdministrators());

        DB::rollBack();
    }

    public function test_inactivating_an_administrator_locks_the_others(): void
    {
        $target = User::factory()->administrator()->create();
        User::factory()->administrator()->create();

        DB::beginTransaction();

        app(ChangeUserStatus::class)->handle($target, false);

        $this->assertBlocked($this->lockActiveAdministrators());

        DB::rollBack();
    }

    public function test_two_administrators_cannot_delete_each_other(): void
    {
        $first = User::factory()->administrator()->create();
        $second = User::factory()->administrator()->create();

        DB::beginTransaction();

        app(DeleteUser::class)->handle($first);

        $this->assertBlocked(fn () => User::on('second')->whereKey($first->id)->lockForUpdate()->first());
        $this->assertBlocked(fn () => User::on('second')->whereKey($second->id)->lockForUpdate()->first());

        DB::commit();

        $this->assertFalse(User::whereKey($first->id)->exists());
        $this->assertTrue(User::whereKey($second->id)->exists());
    }
}
