<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function getAuthenticatedEmployee()
    {
    $user = Auth::user();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'operator' => [  // Sesuaikan dengan struktur yang diharapkan frontend
                'nip' => $user->nip,
                'nama' => $user->nama,
                'email' => $user->email,
                'noTelp' => $user->no_telp,  // Sesuaikan dengan camelCase di frontend
                'alamat' => $user->alamat
            ]
        ]
    ]);
    }
}
