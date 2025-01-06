<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected function okResponse($message, $data = [])
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }
    public function login(Request $request){
        $loginData = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $loginData['email'])->first();

        if(!$user || !Hash::check($loginData['password'], $user->password)){
            return response(['message' => 'Email atau Password salah']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $userData = array_merge($user->toArray(), ['token' => $token]);

        return $this->okResponse("Login Berhasil", ['user' => $userData]);
    }

    public function logout(Request $request){
        // Mencabut token saat ini
        $request->user()->currentAccessToken()->delete();

        // Mengembalikan respon sukses
        return response()->json(['message' => 'Logout berhasil'], 200);
    }
}
