<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('database')]
class DatabaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function migrations_execute_without_errors(): void
    {
        $exitCode = Artisan::call('migrate:fresh');

        $this->assertSame(0, $exitCode);
    }

    #[Test]
    public function users_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'name',
            'email',
            'password',
            'country',
            'department',
            'currency_code',
            'created_at',
            'updated_at',
        ]));
    }

    #[Test]
    public function users_table_has_indexes(): void
    {
        $indexes = Schema::getIndexes('users');

        $indexNames = array_column($indexes, 'name');

        $this->assertContains('users_email_unique', $indexNames);
    }

    #[Test]
    public function payments_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));

        $this->assertTrue(Schema::hasColumns('payments', [
            'id',
            'user_id',
            'amount_local',
            'currency_code',
            'amount_eur',
            'exchange_rate',
            'rate_source',
            'rate_timestamp',
            'pending',
            'description',
            'approved_by',
            'approved_at',
            'expired_at',
            'created_at',
            'updated_at',
        ]));
    }

    #[Test]
    public function payments_has_foreign_key_to_users(): void
    {
        $foreignKeys = Schema::getForeignKeys('payments');

        $foreignKeyNames = array_column($foreignKeys, 'name');

        $this->assertContains('payments_user_id_foreign', $foreignKeyNames);
        $this->assertContains('payments_approved_by_foreign', $foreignKeyNames);
    }

    #[Test]
    public function payments_has_index_on_pending(): void
    {
        $indexes = Schema::getIndexes('payments');

        $indexNames = array_column($indexes, 'name');

        $this->assertContains('payments_pending_index', $indexNames);
    }

    #[Test]
    public function rollback_reverts_all_migrations(): void
    {
        Artisan::call('migrate:fresh');
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('payments'));

        Artisan::call('migrate:rollback');

        $this->assertFalse(Schema::hasTable('payments'));
    }
}
