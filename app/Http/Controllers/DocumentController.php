<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_kontrak' => 'required|string|unique:documents',
            'tanggal_kontrak' => 'required|date',
            'paket_pekerjaan' => 'required|string',
            'tahun_anggaran' => 'required|string',
            'nomor_pp' => 'required|string',
            'tanggal_pp' => 'required|date',
            'nomor_hps' => 'required|string',
            'tanggal_hps' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'nomor_pph1' => 'required|string',
            'tanggal_pph1' => 'required|date',
            'nomor_pph2' => 'required|string',
            'tanggal_pph2' => 'required|date',
            'nomor_ukn' => 'required|string',
            'tanggal_ukn' => 'required|date',
            'tanggal_undangan_ukn' => 'required|date',
            'nomor_ba_ekn' => 'required|string',
            'nomor_pppb' => 'required|string',
            'tanggal_pppb' => 'required|date',
            'nomor_lppb' => 'required|string',
            'tanggal_lppb' => 'required|date',
            'nomor_ba_stp' => 'required|string',
            'nomor_ba_pem' => 'required|string',
            'nomor_dipa' => 'required|string',
            'tanggal_dipa' => 'required|date',
            'kode_kegiatan' => 'required|string',
            'id_vendor' => 'required|exists:vendors,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $document = Document::create($request->all());

            return response()->json([
                'message' => 'Data dokumen berhasil disimpan',
                'data' => $document->load(['vendor'])
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
            $document = Document::where('nomor_kontrak', $nomor_kontrak)
                ->with(['vendor', 'contract', 'documentOfficial.official'])
                ->firstOrFail();
            
            return response()->json([
                'message' => 'Data dokumen berhasil diambil',
                'data' => $document
            ], 200);
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
            $documents = Document::with(['vendor', 'contract'])
                ->get();
            
            return response()->json([
                'message' => 'Data dokumen berhasil diambil',
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
