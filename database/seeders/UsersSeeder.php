<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::create([
                'username' => 'admin',
                'first_name'=>'admin',
                'last_name'=>'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
            ])->assignRole('admin');
        }
    
        if (!User::where('email', 'user@user.com')->exists()) {
            User::create([
                'username' => 'user',
                'first_name'=>'user',
                'last_name'=>'user',
                'email' => 'user@user.com',
                'password' => Hash::make('password'),
            ])->assignRole('user');
        }
    }
}
