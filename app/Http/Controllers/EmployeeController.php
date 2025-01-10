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
use Illuminate\Support\Facades\Log;

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

    public function updateVendor(Request $request, $id)
    {
    $validator = Validator::make($request->all(), [
        'nama_vendor' => 'required|string|unique:vendors,nama_vendor,'.$id,
        'alamat_vendor' => 'required|string',
        'nama_pj' => 'required|string',
        'jabatan_pj' => 'required|string',
        'npwp' => 'required|string|unique:vendors,npwp,'.$id,
        'bank_vendor' => 'required|string',
        'norek_vendor' => 'required|string|unique:vendors,norek_vendor,'.$id,
        'nama_rek_vendor' => 'required|string',
    ]);

    if ($validator->fails()){
        return response()->json($validator->errors(), 400);
    }

    DB::beginTransaction();

    try {
        $vendor = Vendor::findOrFail($id);
        $vendor->update($request->all());
        
        DB::commit();
        
        return response()->json([
            'message' => 'Data vendor berhasil diperbarui',
            'data' => $vendor
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Terjadi kesalahan saat memperbarui vendor'], 500);
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

    public function updateOfficial(Request $request, $nip)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|unique:officials,nip,' . $nip . ',nip',
            'nama' => 'required|string',
            'jabatan' => 'required|string',
            'periode_jabatan' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $official = Official::findOrFail($nip);
            
            // Check if NIP is being updated
            $newNip = $request->nip;
            if ($nip !== $newNip) {
                // Update related records in document_official table first
                DB::table('documents_officials')
                    ->where('nip', $nip)
                    ->update(['nip' => $newNip]);
    
                // Create a new record with the new NIP
                $newOfficial = $official->replicate();
                $newOfficial->nip = $newNip;
                $newOfficial->fill($request->all());
                $newOfficial->save();
                
                // Delete the old record
                $official->delete();
                
                $official = $newOfficial;
            } else {
                // Update existing record if NIP hasn't changed
                $official->update($request->all());
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Data pejabat berhasil diperbarui',
                'data' => $official
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating official: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui pejabat',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function addDocument(Request $request)
    {
        // Get the latest vendor ID
        $latestVendor = Vendor::latest('id')->first();
        
        if (!$latestVendor) {
            return response()->json(['error' => 'Tidak ada vendor yang tersedia'], 400);
        }
    
        // Merge the vendor ID into the request data
        $requestData = array_merge($request->all(), ['id_vendor' => $latestVendor->id]);
    
        $validator = Validator::make($requestData, [
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
            $document = Document::create($requestData);
            
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

    public function updateDocument(Request $request, $nomor_kontrak)
    {
        // Get the latest vendor ID
        $latestVendor = Vendor::latest('id')->first();
        
        if (!$latestVendor) {
            return response()->json(['error' => 'Tidak ada vendor yang tersedia'], 400);
        }
    
        // Merge the vendor ID into the request data
        $requestData = array_merge($request->all(), ['id_vendor' => $latestVendor->id]);
    
        // Add nomor_kontrak to validation rules
        $validator = Validator::make($requestData, [
            'nomor_kontrak' => 'required|string|unique:documents,nomor_kontrak,' . $nomor_kontrak . ',nomor_kontrak',
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
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $document = Document::findOrFail($nomor_kontrak);
            
            // Check if nomor_kontrak is being updated
            $newNomorKontrak = $requestData['nomor_kontrak'];
            if ($nomor_kontrak !== $newNomorKontrak) {
                // Create a new record with the new nomor_kontrak
                $newDocument = $document->replicate();
                $newDocument->nomor_kontrak = $newNomorKontrak;
                $newDocument->fill($requestData);
                $newDocument->save();
                
                // Delete the old record
                $document->delete();
                
                $document = $newDocument;
            } else {
                // Update existing record if nomor_kontrak hasn't changed
                $document->update($requestData);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Data dokumen berhasil diperbarui',
                'data' => $document
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat memperbarui dokumen'], 500);
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
