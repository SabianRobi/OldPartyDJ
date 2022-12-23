<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@partydj.com',
            'is_admin' => true,
            'password' => Hash::make('admin'),
        ]);
        
        \App\Models\User::factory(10)->create();
    }
}
