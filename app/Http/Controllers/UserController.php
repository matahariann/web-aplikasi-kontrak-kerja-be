<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    protected function okResponse($message, $data = [])
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }
    
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $loginData['email'])->first();

        if(!$user) {
            return response(['message' => 'Email tidak ditemukan'], 404);
        }

        if(!$user->is_verified) {
            return response(['message' => 'Akun belum diverifikasi'], 403);
        }

        if(!Hash::check($loginData['password'], $user->password)) {
            return response(['message' => 'Password salah'], 401);
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

    public function register(Request $request)
    {
    $validatedData = $request->validate([
        'nip' => 'required|string|unique:users',
        'nama' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users|max:255',
        'password' => 'required|string|min:8|confirmed',
    ], [
        'nip.unique' => 'NIP sudah terdaftar',
        'email.unique' => 'Email sudah terdaftar',
        'password.confirmed' => 'Konfirmasi password tidak sesuai',
    ]);

    // Generate verification code
    $verificationCode = sprintf("%06d", mt_rand(1, 999999));

    $user = User::create([
        'nip' => $validatedData['nip'],
        'nama' => $validatedData['nama'],
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
        'verification_code' => $verificationCode,
        'verification_code_expires_at' => Carbon::now()->addMinutes(15),
        'is_verified' => false
    ]);

    // Send verification email
    Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

    $token = $user->createToken('auth_token')->plainTextToken;
    
    return $this->okResponse('Registrasi berhasil, silakan verifikasi email', [
        'user_id' => $user->id,
        'email' => $user->email,
    ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', // Validate against 'id'
            'verification_code' => 'required|string'
        ]);
    
        $user = User::findOrFail($request->user_id);
    
        // Verification logic remains the same
        if (!$user->verification_code) {
            return response()->json(['message' => 'Kode verifikasi tidak valid'], 400);
        }
    
        if ($user->verification_code !== $request->verification_code || 
            Carbon::now()->greaterThan($user->verification_code_expires_at)) {
            
            return response()->json(['message' => 'Kode verifikasi salah atau sudah kedaluwarsa'], 400);
        }
    
        $user->update([
            'is_verified' => true,
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'email_verified_at' => now()
        ]);
    
        return $this->okResponse('Email berhasil diverifikasi');
    }

    public function resendVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate new verification code
        $verificationCode = sprintf("%06d", mt_rand(1, 999999));

        // Update user's verification details
        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => Carbon::now()->addMinutes(15)
        ]);

        // Resend verification email
        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return $this->okResponse('Kode verifikasi baru telah dikirim');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        // Generate verification code
        $verificationCode = sprintf("%06d", mt_rand(1, 999999));
    
        // Update user's verification details
        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => Carbon::now()->addMinutes(15)
        ]);
    
        // Send reset password email
        Mail::to($user->email)->send(new ResetPasswordMail($verificationCode));
    
        return $this->okResponse('Kode verifikasi telah dikirim ke email Anda', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::findOrFail($request->user_id);

    $user->update([
        'password' => Hash::make($request->password),
        'verification_code' => null,
        'verification_code_expires_at' => null
    ]);

    return $this->okResponse('Password berhasil diubah');
    }

    public function resendResetPasswordCode(Request $request)
    {
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $user = User::where('email', $request->email)->first();

    // Generate new verification code
    $verificationCode = sprintf("%06d", mt_rand(1, 999999));

    // Update user's verification details
    $user->update([
        'verification_code' => $verificationCode,
        'verification_code_expires_at' => Carbon::now()->addMinutes(15)
    ]);

    // Send reset password verification email
    Mail::to($user->email)->send(new ResetPasswordMail($verificationCode));

    return $this->okResponse('Kode verifikasi baru telah dikirim');
    }
}
