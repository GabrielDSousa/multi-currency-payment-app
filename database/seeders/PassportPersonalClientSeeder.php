<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PassportPersonalClientSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            $this->command->error('The oauth_clients table does not exist. Please run the migrations first.');

            return;
        }

        $exists = DB::table('oauth_clients')
            ->where('owner_type', null)
            ->where('owner_id', null)
            ->where('name', config('app.name'))
            ->where('provider', 'users')
            ->where('grant_types', 'like', '%personal_access%')
            ->where('revoked', false)
            ->exists();

        if ($exists) {
            $this->command->info('Personal access client already exists. Skipping seeding.');

            return;
        }

        Artisan::call('passport:client', [
            '--personal' => true,
            '--no-interaction' => true,
        ]);
    }
}
