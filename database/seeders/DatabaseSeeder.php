<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use \App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //admin & Users
        $admin = User::factory()->makeOne([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@partydj.com',
            'password' => Hash::make('admin'),
        ]);
        $admin->is_admin = true;
        $admin->save();

        \App\Models\User::factory(10)->create();
    }
}
