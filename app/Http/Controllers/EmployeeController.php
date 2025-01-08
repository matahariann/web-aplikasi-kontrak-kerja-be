<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function addVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_vendor' => 'required|string|unique:vendors',
            'alamat_vendor' => 'required|string',
            'nama_pj' => 'required|string',
            'jabatan_pj' => 'required|string',
            'npwp' => 'required|string|unique:vendors',
            'bank_vendor' => 'required|string',
            'norek_vendor' => 'required|string|unique:vendors',
            'nama_rek_vendor' => 'required|string',
        ]);

        if ($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();

        try{
            $vendor = Vendor::create($request->all());
            
            DB::commit();

            return response()->json([
                'message' => 'Data vendor berhasil disimpan',
                'data' => $vendor
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat menambahkan vendor'], 500);
        }
    }

    public function deleteVendor($id)
    {
    DB::beginTransaction();
    
    try {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();
        
        DB::commit();
        
        return response()->json([
            'message' => 'Data vendor berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Terjadi kesalahan saat menghapus vendor'], 500);
    }
    }
}
