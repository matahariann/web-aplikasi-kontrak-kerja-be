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
            'no_telp' => '085641626353',
            'alamat' => 'Jl. Raya Ciputat Parung No. 1',
            'password' => Hash::make('password'),
        ]);
        
        User::create([
            'nip' => '198305152009041003',
            'nama' => 'Budi',
            'email' => 'budi@gmail.com',
            'no_telp' => '081234567890',
            'alamat' => 'Jl. Merdeka No. 2',
            'password' => Hash::make('password'),
        ]);
    }
}
