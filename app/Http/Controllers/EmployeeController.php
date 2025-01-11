<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentOfficial;
use App\Models\Images;
use App\Models\Official;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule as ValidationRule;

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
            $newNip = $request->nip;
    
            if ($nip !== $newNip) {
                // 1. Buat record baru dengan NIP baru
                $newOfficial = new Official();
                $newOfficial->nip = $newNip;
                $newOfficial->nama = $request->nama;
                $newOfficial->jabatan = $request->jabatan;
                $newOfficial->periode_jabatan = $request->periode_jabatan;
                $newOfficial->save();
    
                // 2. Update records di documents_officials dengan NIP baru
                DocumentOfficial::where('nip', $nip)
                    ->update(['nip' => $newNip]);
    
                // 3. Hapus record lama
                $official->delete();
                
                $official = $newOfficial;
            } else {
                // Update data lainnya jika NIP tidak berubah
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
                // Update related records in documents_officials table first
                DB::table('documents_officials')
                    ->where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);
    
                // Update related records in contracts table if it exists
                DB::table('contracts')
                    ->where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);
    
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
            Log::error('Error updating document: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    public function addContract(Request $request)
    {
        // Ambil nomor kontrak terakhir dari tabel documents
        $lastDocument = Document::orderBy('created_at', 'desc')->first();
        
        if (!$lastDocument) {
            return response()->json(['error' => 'Tidak ada dokumen yang tersedia'], 404);
        }
    
        $validator = Validator::make($request->all(), [
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
    
        try {
            // Tambahkan nomor kontrak dari dokumen terakhir ke data request
            $contractData = $request->all();
            $contractData['nomor_kontrak'] = $lastDocument->nomor_kontrak;
    
            $contract = Contract::create($contractData);
            
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil disimpan',
                'data' => $contract
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat menambahkan kontrak'], 500);
        }
    }

    public function updateContract(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
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
    
        try {
            $contract = Contract::findOrFail($id);
            $oldNomorKontrak = $contract->nomor_kontrak;
    
            // Cek apakah ada permintaan untuk mengubah nomor kontrak
            if ($request->has('nomor_kontrak') && $request->nomor_kontrak !== $oldNomorKontrak) {
                // Validasi nomor kontrak baru
                $newNomorKontrak = $request->nomor_kontrak;
                $documentExists = Document::where('nomor_kontrak', $newNomorKontrak)->exists();
                
                if (!$documentExists) {
                    DB::rollBack();
                    return response()->json(['error' => 'Nomor kontrak tidak ditemukan dalam dokumen'], 404);
                }
    
                // Buat record baru dengan nomor kontrak baru
                $newContract = new Contract();
                $newContract->nomor_kontrak = $newNomorKontrak;
                $newContract->jenis_kontrak = $request->jenis_kontrak;
                $newContract->deskripsi = $request->deskripsi;
                $newContract->jumlah_orang = $request->jumlah_orang;
                $newContract->durasi_kontrak = $request->durasi_kontrak;
                $newContract->nilai_kontral_awal = $request->nilai_kontral_awal;
                $newContract->nilai_kontrak_akhir = $request->nilai_kontrak_akhir;
                $newContract->save();
    
                // Hapus record lama
                $contract->delete();
                
                $contract = $newContract;
            } else {
                // Update data lainnya jika nomor kontrak tidak berubah
                $contract->update($request->except('nomor_kontrak'));
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil diperbarui',
                'data' => $contract
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating contract: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui kontrak',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function saveDocumentWithOfficials(Request $request)
    {
        // Modify validation rules to match nested document structure
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'document.nomor_kontrak' => 'required|string|unique:documents,nomor_kontrak',
            'document.tanggal_kontrak' => 'required|date',
            'document.paket_pekerjaan' => 'required|string',
            'document.tahun_anggaran' => 'required|string',
            'document.nomor_pp' => 'required|string',
            'document.tanggal_pp' => 'required|date',
            'document.nomor_hps' => 'required|string',
            'document.tanggal_hps' => 'required|date',
            'document.tanggal_mulai' => 'required|date',
            'document.tanggal_selesai' => 'required|date',
            'document.nomor_pph1' => 'required|string',
            'document.tanggal_pph1' => 'required|date',
            'document.nomor_pph2' => 'required|string',
            'document.tanggal_pph2' => 'required|date',
            'document.nomor_ukn' => 'required|string',
            'document.tanggal_ukn' => 'required|date',
            'document.tanggal_undangan_ukn' => 'required|date',
            'document.nomor_ba_ekn' => 'required|string',
            'document.nomor_pppb' => 'required|string',
            'document.tanggal_pppb' => 'required|date',
            'document.nomor_lppb' => 'required|string',
            'document.tanggal_lppb' => 'required|date',
            'document.nomor_ba_stp' => 'required|string',
            'document.nomor_ba_pem' => 'required|string',
            'document.nomor_dipa' => 'required|string',
            'document.tanggal_dipa' => 'required|date',
            'document.kode_kegiatan' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Get latest vendor
            $latestVendor = Vendor::latest('id')->first();
            if (!$latestVendor) {
                throw new \Exception('Tidak ada vendor yang tersedia');
            }
    
            // Add vendor ID to document data
            $documentData = array_merge($request->input('document'), [
                'id_vendor' => $latestVendor->id
            ]);
    
            // Create document
            $document = Document::create($documentData);
    
            // Create document-official relationships
            foreach ($request->input('officials') as $officialData) {
                DocumentOfficial::create([
                    'nip' => $officialData['nip'],
                    'nomor_kontrak' => $document->nomor_kontrak
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data dokumen dan pejabat berhasil disimpan',
                'data' => [
                    'document' => $document,
                    'officials' => $request->input('officials')
                ]
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving document with officials: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDocumentWithOfficials(Request $request, $nomor_kontrak)
    {
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'document.nomor_kontrak' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('documents', 'nomor_kontrak')->ignore($nomor_kontrak, 'nomor_kontrak')
            ],
            'document.tanggal_kontrak' => 'required|date',
            'document.paket_pekerjaan' => 'required|string',
            'document.tahun_anggaran' => 'required|string',
            'document.nomor_pp' => 'required|string',
            'document.tanggal_pp' => 'required|date',
            'document.nomor_hps' => 'required|string',
            'document.tanggal_hps' => 'required|date',
            'document.tanggal_mulai' => 'required|date',
            'document.tanggal_selesai' => 'required|date',
            'document.nomor_pph1' => 'required|string',
            'document.tanggal_pph1' => 'required|date',
            'document.nomor_pph2' => 'required|string',
            'document.tanggal_pph2' => 'required|date',
            'document.nomor_ukn' => 'required|string',
            'document.tanggal_ukn' => 'required|date',
            'document.tanggal_undangan_ukn' => 'required|date',
            'document.nomor_ba_ekn' => 'required|string',
            'document.nomor_pppb' => 'required|string',
            'document.tanggal_pppb' => 'required|date',
            'document.nomor_lppb' => 'required|string',
            'document.tanggal_lppb' => 'required|date',
            'document.nomor_ba_stp' => 'required|string',
            'document.nomor_ba_pem' => 'required|string',
            'document.nomor_dipa' => 'required|string',
            'document.tanggal_dipa' => 'required|date',
            'document.kode_kegiatan' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $document = Document::findOrFail($nomor_kontrak);
            $latestVendor = Vendor::latest('id')->first();
            
            if (!$latestVendor) {
                throw new \Exception('Tidak ada vendor yang tersedia');
            }

            $documentData = array_merge($request->input('document'), [
                'id_vendor' => $latestVendor->id
            ]);

            $newNomorKontrak = $documentData['nomor_kontrak'];
            
            if ($nomor_kontrak !== $newNomorKontrak) {
                // 1. Create new document first
                $newDocument = Document::create($documentData);

                // 2. Update existing contracts to reference new document
                Contract::where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);

                // 3. Update document officials
                DocumentOfficial::where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);
                
                // 4. Delete old document (this will cascade properly now)
                $document->delete();
                
                $document = $newDocument;
            } else {
                // If nomor_kontrak is not changed, just update normally
                $document->update($documentData);
                
                // Update officials
                DocumentOfficial::where('nomor_kontrak', $document->nomor_kontrak)->delete();
                
                foreach ($request->input('officials') as $officialData) {
                    DocumentOfficial::create([
                        'nip' => $officialData['nip'],
                        'nomor_kontrak' => $document->nomor_kontrak
                    ]);
                }
            }

            DB::commit();

            // Refresh the document with its relationships
            $document = Document::with(['vendor', 'contracts', 'documentOfficial'])
                ->find($newNomorKontrak);

            return response()->json([
                'message' => 'Data dokumen dan pejabat berhasil diperbarui',
                'data' => [
                    'document' => $document,
                    'officials' => $request->input('officials')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating document with officials: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui data',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function showImage($id)
    {
        $image = Images::findOrFail($id);
    
        // Baca file gambar dan konversi ke base64
        $path = storage_path('app/public/' . $image->image);
        $imageData = base64_encode(file_get_contents($path));
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $image->id,
                'name' => $image->name,
                'image' => $imageData // Kirim data gambar dalam format base64
            ]
        ]);
    }
}
