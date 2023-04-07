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
        //admin
        $admin = new User();
        $admin->name = 'Admin User';
        $admin->username = 'admin';
        $admin->email = 'admin@partydj.com';
        $admin->password = Hash::make('admin');
        $admin->save();

        //Users
        for ($i=0; $i < 5; $i++) {
            $user = new User();
            $user->name = 'Example User ' . $i;
            $user->username = 'ExampleUser' . $i;
            $user->email = 'exampleuser' . $i . '@partydj.com';
            $user->password = Hash::make('password');
            $user->save();
        }
    }
}
