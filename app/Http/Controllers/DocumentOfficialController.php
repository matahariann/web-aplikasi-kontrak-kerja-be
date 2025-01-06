<?php

namespace App\Http\Controllers;

use App\Models\DocumentOfficial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentOfficialController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|exists:officials,nip',
            'nomor_kontrak' => 'required|string|exists:documents,nomor_kontrak',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Check if relation already exists
            $exists = DocumentOfficial::where('nip', $request->nip)
                ->where('nomor_kontrak', $request->nomor_kontrak)
                ->exists();
            
            if ($exists) {
                return response()->json([
                    'message' => 'Relasi dokumen dan pejabat sudah ada'
                ], 422);
            }

            $documentOfficial = DocumentOfficial::create($request->all());
            
            return response()->json([
                'message' => 'Relasi dokumen dan pejabat berhasil disimpan',
                'data' => $documentOfficial
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDocument($nomor_kontrak)
    {
        try {
            $officials = DocumentOfficial::where('nomor_kontrak', $nomor_kontrak)
                ->with('official')
                ->get();
            
            return response()->json([
                'message' => 'Data pejabat dokumen berhasil diambil',
                'data' => $officials
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByOfficial($nip)
    {
        try {
            $documents = DocumentOfficial::where('nip', $nip)
                ->with('document')
                ->get();
            
            return response()->json([
                'message' => 'Data dokumen pejabat berhasil diambil',
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
