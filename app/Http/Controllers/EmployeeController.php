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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            'nip' => 'required|string',
            'nama' => 'required|string',
            'jabatan' => 'required|string',
            'periode_jabatan' => 'required|string',
            'surat_keputusan' => 'nullable|string', 
        ]);
        
        if ($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try{
            // Ensure the combination of nip and periode_jabatan is unique
            $exists = Official::where('nip', $request->nip)
                             ->where('periode_jabatan', $request->periode_jabatan)
                             ->exists();
            if ($exists) {
                return response()->json(['error' => 'The combination of NIP and Periode Jabatan already exists.'], 400);
            }
    
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

    public function updateOfficial(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string',
            'nama' => 'required|string',
            'jabatan' => 'required|string',
            'periode_jabatan' => 'required|string',
            'surat_keputusan' => 'nullable|string', 
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $official = Official::findOrFail($id);
    
            // Check if the new combination of nip and periode_jabatan already exists
            if (($official->nip !== $request->nip) || ($official->periode_jabatan !== $request->periode_jabatan)) {
                $exists = Official::where('nip', $request->nip)
                                 ->where('periode_jabatan', $request->periode_jabatan)
                                 ->exists();
                if ($exists) {
                    return response()->json(['error' => 'The combination of NIP and Periode Jabatan already exists.'], 400);
                }
            }
    
            $official->update($request->all());
    
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

    public function getPeriodes()
    {
    try {
        $periodes = Official::select('periode_jabatan')
            ->distinct()
            ->get()
            ->pluck('periode_jabatan');

        return response()->json([
            'status' => 'success',
            'data' => $periodes
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data periode'
        ], 500);
    }
    }

    public function getOfficialsByPeriode($periode)
    {
    try {
        $officials = Official::where('periode_jabatan', $periode)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $officials
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data pejabat'
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

    public function deleteContract($id)
    {
    DB::beginTransaction();

    try {
        $contract = Contract::findOrFail($id);
        
        // Hapus kontrak
        $contract->delete();
        
        DB::commit();

        return response()->json([
            'message' => 'Data kontrak berhasil dihapus',
            'data' => $contract
        ]);

    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Kontrak tidak ditemukan'
        ], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting contract: ' . $e->getMessage());
        return response()->json([
            'error' => 'Terjadi kesalahan saat menghapus kontrak',
            'detail' => $e->getMessage()
        ], 500);
    }
    }

    public function saveDocumentWithOfficials(Request $request)
    {
        // Modify validation rules to include periode_jabatan and ensure uniqueness
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'officials.*.periode_jabatan' => 'required|string|exists:officials,periode_jabatan',
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
                $official = Official::where('nip', $officialData['nip'])
                                    ->where('periode_jabatan', $officialData['periode_jabatan'])
                                    ->first();
    
                if (!$official) {
                    throw new \Exception('Official not found with provided NIP and Periode Jabatan');
                }
    
                DocumentOfficial::create([
                    'official_id' => $official->id,
                    'nomor_kontrak' => $document->nomor_kontrak
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data dokumen dan pejabat berhasil disimpan',
                'data' => [
                    'document' => $document,
                    'officials' => $document->officials
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
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'officials.*.periode_jabatan' => 'required|string|exists:officials,periode_jabatan',
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
    
        // Handle validation failures
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Retrieve the existing document
            $document = Document::findOrFail($nomor_kontrak);
            $latestVendor = Vendor::latest('id')->first();
    
            if (!$latestVendor) {
                throw new \Exception('Tidak ada vendor yang tersedia');
            }
    
            // Merge request data with the latest vendor ID
            $documentData = array_merge($request->input('document'), [
                'id_vendor' => $latestVendor->id
            ]);
    
            $newNomorKontrak = $documentData['nomor_kontrak'];
    
            if ($nomor_kontrak !== $newNomorKontrak) {
                // Check if the new contract number already exists
                if (Document::where('nomor_kontrak', $newNomorKontrak)->exists()) {
                    throw new \Exception('Nomor kontrak baru sudah ada');
                }
    
                // Create a new document with the updated contract number
                $newDocument = Document::create($documentData);
    
                // Update related contracts to reference the new contract number
                // Assuming Contract model exists and has 'nomor_kontrak' field
                Contract::where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);
    
                // Update related documents_officials to reference the new contract number
                DocumentOfficial::where('nomor_kontrak', $nomor_kontrak)
                    ->update(['nomor_kontrak' => $newNomorKontrak]);
    
                // Delete the old document
                $document->delete();
    
                // Set the document variable to the new document for further processing
                $document = $newDocument;
            } else {
                // If nomor_kontrak is not changed, just update the existing document
                $document->update($documentData);
            }
    
            // Process the officials data
            $officials = $request->input('officials');
    
            // Collect official IDs based on provided NIP and periode_jabatan
            $officialIds = [];
            foreach ($officials as $officialData) {
                $official = Official::where('nip', $officialData['nip'])
                                    ->where('periode_jabatan', $officialData['periode_jabatan'])
                                    ->first();
    
                if (!$official) {
                    throw new \Exception('Official not found with provided NIP and Periode Jabatan');
                }
    
                $officialIds[] = $official->id;
            }
    
            if ($nomor_kontrak !== $newNomorKontrak) {
                // If nomor_kontrak was changed, documentOfficials are already updated
                // No further action needed
            } else {
                // If nomor_kontrak is not changed, synchronize the officials
                // First, delete existing relationships
                DocumentOfficial::where('nomor_kontrak', $document->nomor_kontrak)->delete();
    
                // Then, create new relationships
                foreach ($officialIds as $officialId) {
                    DocumentOfficial::create([
                        'official_id' => $officialId,
                        'nomor_kontrak' => $document->nomor_kontrak
                    ]);
                }
            }
    
            DB::commit();
    
            // Reload the document with its relationships
            // Since 'officials' relationship does not exist, use 'documentOfficials.official'
            $document = Document::with(['vendor', 'contracts', 'documentOfficials.official'])
                ->find($document->nomor_kontrak);
    
            // Transform documentOfficials to extract officials data
            $officialsData = $document->documentOfficials->map(function ($docOfficial) {
                return $docOfficial->official;
            });
    
            return response()->json([
                'message' => 'Data dokumen dan pejabat berhasil diperbarui',
                'data' => [
                    'document' => $document,
                    'officials' => $officialsData
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
