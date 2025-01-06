<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_kontrak' => 'required|string|exists:documents,nomor_kontrak',
            'jenis_kontrak' => 'required|string',
            'deskripsi' => 'required|string',
            'jumlah_orang' => 'required|integer',
            'durasi_kontrak' => 'required|integer',
            'nilai_kontral_awal' => 'required|numeric',
            'nilai_kontrak_akhir' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $contract = Contract::create($request->all());
            
            return response()->json([
                'message' => 'Data kontrak berhasil disimpan',
                'data' => $contract
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($nomor_kontrak)
    {
        try {
            $contract = Contract::where('nomor_kontrak', $nomor_kontrak)
                ->with('document')
                ->firstOrFail();
            
            return response()->json([
                'message' => 'Data kontrak berhasil diambil',
                'data' => $contract
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
