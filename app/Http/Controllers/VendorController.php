<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_vendor' => 'required|string',
            'alamat_vendor' => 'required|string',
            'nama_pj' => 'required|string',
            'jabatan_pj' => 'required|string',
            'npwp' => 'required|string|unique:vendors',
            'bank_vendor' => 'required|string',
            'norek_vendor' => 'required|string|unique:vendors',
            'nama_rek_vendor' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $vendor = Vendor::create($request->all());
            
            return response()->json([
                'message' => 'Data vendor berhasil disimpan',
                'data' => $vendor
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $vendors = Vendor::all();
            return response()->json([
                'message' => 'Data vendor berhasil diambil',
                'data' => $vendors
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            return response()->json([
                'message' => 'Data vendor berhasil diambil',
                'data' => $vendor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
