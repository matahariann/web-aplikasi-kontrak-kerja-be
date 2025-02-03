<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'nip' => '198102052008031002',
            'nama' => 'Sunan',
            'email' => 'sunan@gmail.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
        ]);
    }
}
