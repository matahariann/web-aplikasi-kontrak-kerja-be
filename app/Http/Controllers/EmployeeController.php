<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Official;
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

    public function addOfficial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|unique:officials',
            'nama' => 'required|string',
            'jabatan' => 'required|string',
            'periode_jabatan' => 'required|string',
        ]);
        
        if ($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();

        try{
            $official = Official::create($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Data pejabat berhasil disimpan',
                'data' => $official
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat menambahkan pejabat'], 500);
        }
    }

    public function deleteOfficial($nip)
    {
        DB::beginTransaction();
        
        try {
            $official = Official::findOrFail($nip); // Menggunakan NIP sebagai primary key
            $official->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Data pejabat berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus pejabat',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addDocument(Request $request)
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

        if ($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();

        try{
            $document = Document::create($request->all());
            
            DB::commit();

            return response()->json([
                'message' => 'Data document berhasil disimpan',
                'data' => $document
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat menambahkan document'], 500);
        }
    }

    public function deleteDocument($id)
    {
    DB::beginTransaction();
    
    try {
        $document = Document::findOrFail($id);
        $document->delete();
        
        DB::commit();
        
        return response()->json([
            'message' => 'Data dokumen berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Terjadi kesalahan saat menghapus dokumen'], 500);
    }
    }

    public function addContract(Request $request)
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

        DB::beginTransaction();

        try{
            $contract = Contract::create($request->all());
            
            DB::commit();

            return response()->json([
                'message' => 'Data kontrak berhasil disimpan',
                'data' => $contract
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat menambahkan kontrak'], 500);
        }
    }

    public function deleteContract($id)
    {
    DB::beginTransaction();
    
    try {
        $contract = Contract::findOrFail($id);
        $contract->delete();
        
        DB::commit();
        
        return response()->json([
            'message' => 'Data kontrak berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Terjadi kesalahan saat menghapus kontrak'], 500);
    }
    }
}
