<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Use system connection
        DB::connection('system')->table('accounts')->insert([
            [
                'username' => 'ats',
                'password' => Hash::make('atsatsats'),
                'email' => null,
                'telephone' => null,
                'role' => 'admin',
                'database_file' => 'account_1.sqlite',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'orga_test',
                'password' => Hash::make('atsatsats'),
                'email' => null,
                'telephone' => null,
                'role' => 'chrono',
                'database_file' => 'account_2.sqlite',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'viewer_test',
                'password' => Hash::make('atsatsats'),
                'email' => null,
                'telephone' => null,
                'role' => 'viewer',
                'database_file' => 'account_3.sqlite',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('3 test accounts created successfully!');
    }
}
