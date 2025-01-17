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
use Illuminate\Support\Str;

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
            'employee' => [ 
                'nip' => $user->nip,
                'nama' => $user->nama,
                'email' => $user->email,
                'noTelp' => $user->no_telp, 
                'alamat' => $user->alamat
            ]
        ]
    ]);
    }

    private function getOrCreateFormSession(Request $request)
    {
        $formSessionId = $request->input('form_session_id');
        
        if (!$formSessionId) {
            $formSessionId = (string) Str::uuid();
        }
        
        return $formSessionId;
    }

    public function addVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_vendor' => 'required|string',
            'alamat_vendor' => 'required|string',
            'nama_pj' => 'required|string',
            'jabatan_pj' => 'required|string',
            'npwp' => 'required|string',
            'bank_vendor' => 'required|string',
            'norek_vendor' => 'required|string',
            'nama_rek_vendor' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Gunakan form_session_id dari request jika ada, jika tidak buat baru
            $formSessionId = $request->input('form_session_id') ?? (string) Str::uuid();
            
            $vendorData = array_merge($request->all(), [
                'form_session_id' => $formSessionId
            ]);
    
            $vendor = Vendor::create($vendorData);
            
            DB::commit();
    
            return response()->json([
                'message' => 'Data vendor berhasil disimpan',
                'data' => $vendor,
                'form_session_id' => $formSessionId
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Terjadi kesalahan saat menambahkan vendor',
                'detail' => $e->getMessage()
            ], 500);
        }
    }


    public function updateVendor(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_vendor' => 'required|string',
            'alamat_vendor' => 'required|string',
            'nama_pj' => 'required|string',
            'jabatan_pj' => 'required|string',
            'npwp' => 'required|string',
            'bank_vendor' => 'required|string',
            'norek_vendor' => 'required|string',
            'nama_rek_vendor' => 'required|string',
        ]);
    
        if ($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $vendor = Vendor::findOrFail($id);
            
            // Pertahankan form_session_id yang ada
            $updateData = array_merge($request->all(), [
                'form_session_id' => $vendor->form_session_id
            ]);
            
            $vendor->update($updateData);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Data vendor berhasil diperbarui',
                'data' => $vendor,
                'form_session_id' => $vendor->form_session_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat memperbarui vendor'], 500);
        }
    }

    public function addOfficial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_session_id' => 'required|uuid',
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
            $vendor = Vendor::where('form_session_id', $request->form_session_id)->first();
            if (!$vendor) {
                return response()->json([
                    'error' => 'Vendor tidak ditemukan untuk form session ini.'
                ], 400);
            }
    
            $exists = Official::where('nip', $request->nip)
                            ->where('periode_jabatan', $request->periode_jabatan)
                            ->exists();
            if ($exists) {
                return response()->json([
                    'error' => 'Kombinasi NIP dan Periode Jabatan sudah ada.'
                ], 400);
            }
    
            $officialData = array_merge($request->all(), [
                'form_session_id' => $request->form_session_id
            ]);
    
            $official = Official::create($officialData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data pejabat berhasil disimpan',
                'data' => $official,
                'form_session_id' => $request->form_session_id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Terjadi kesalahan saat menambahkan pejabat',
                'detail' => $e->getMessage()
            ], 500);
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


public function updateDocumentOfficial(Request $request)
{
    $validator = Validator::make($request->all(), [
        'form_session_id' => 'required|uuid',
        'official_ids' => 'required|array',
        'official_ids.*' => 'required|exists:officials,id'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    DB::beginTransaction();
    try {
        // Cari document yang terkait dengan form_session_id
        $document = Document::where('form_session_id', $request->form_session_id)->first();
        
        if (!$document) {
            return response()->json([
                'error' => 'Document tidak ditemukan untuk form session ini.'
            ], 404);
        }

        // Hapus semua relasi yang ada untuk document ini
        DB::table('documents_officials')
            ->where('nomor_kontrak', $document->nomor_kontrak)
            ->delete();

        // Insert relasi baru
        $documentOfficials = [];
        foreach ($request->official_ids as $officialId) {
            $documentOfficials[] = [
                'official_id' => $officialId,
                'nomor_kontrak' => $document->nomor_kontrak,
                'form_session_id' => $request->form_session_id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('documents_officials')->insert($documentOfficials);

        DB::commit();

        return response()->json([
            'message' => 'Relasi document-official berhasil diperbarui',
            'data' => $documentOfficials
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Terjadi kesalahan saat memperbarui relasi document-official',
            'detail' => $e->getMessage()
        ], 500);
    }
    }

    public function getDocumentBySessionId(Request $request, $formSessionId)
    {
        try {
            $document = Document::where('form_session_id', $formSessionId)->first();
            
            if (!$document) {
                return response()->json([
                    'exists' => false
                ]);
            }
    
            return response()->json([
                'exists' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengecek dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function addContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_kontrak' => 'required|string',
            'deskripsi' => 'required|string',
            'jumlah_orang' => 'required|integer',
            'durasi_kontrak' => 'required|integer',
            'nilai_kontral_awal' => 'required|numeric',
            'nilai_kontrak_akhir' => 'required|numeric',
            'form_session_id' => 'required|uuid'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        DB::beginTransaction();
    
        try {
            $document = Document::where('form_session_id', $request->form_session_id)
                              ->latest()
                              ->first();
    
            if (!$document) {
                throw new \Exception('Vendor tidak ditemukan untuk form session ' . $request->form_session_id);
            }
    
            $contractData = array_merge($request->all(), [
                'nomor_kontrak' => $document->nomor_kontrak,
                'form_session_id' => $request->form_session_id
            ]);
    
            $contract = Contract::create($contractData);
            
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil disimpan',
                'data' => $contract
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Terjadi kesalahan saat menambahkan kontrak',
                'detail' => $e->getMessage(),
            ], 500);
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
    
            if ($request->has('nomor_kontrak') && $request->nomor_kontrak !== $oldNomorKontrak) {
                $newNomorKontrak = $request->nomor_kontrak;
                $documentExists = Document::where('nomor_kontrak', $newNomorKontrak)->exists();
                
                if (!$documentExists) {
                    DB::rollBack();
                    return response()->json(['error' => 'Nomor kontrak tidak ditemukan dalam dokumen'], 404);
                }
    
                $newContract = new Contract();
                $newContract->nomor_kontrak = $newNomorKontrak;
                $newContract->jenis_kontrak = $request->jenis_kontrak;
                $newContract->deskripsi = $request->deskripsi;
                $newContract->jumlah_orang = $request->jumlah_orang;
                $newContract->durasi_kontrak = $request->durasi_kontrak;
                $newContract->nilai_kontral_awal = $request->nilai_kontral_awal;
                $newContract->nilai_kontrak_akhir = $request->nilai_kontrak_akhir;
                $newContract->save();
    
                $contract->delete();
                
                $contract = $newContract;
            } else {
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

    public function addDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'officials.*.periode_jabatan' => 'required|string|exists:officials,periode_jabatan',
            'document' => 'required|array',
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
            'form_session_id' => 'nullable|uuid'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();

        try {
            $formSessionId = $this->getOrCreateFormSession($request);
            
            $vendor = Vendor::where('form_session_id', $formSessionId)->latest()->first();
            
            if (!$vendor) {
                $totalVendors = Vendor::count();
                Log::error('Vendor not found. Debug info:', [
                    'form_session_id' => $formSessionId,
                    'total_vendors_in_system' => $totalVendors,
                    'all_form_session_ids' => Vendor::pluck('form_session_id')->toArray()
                ]);
                
                throw new \Exception('No vendor found for form session ID: ' . $formSessionId);
            }

            foreach ($request->input('officials') as $officialData) {
                $official = Official::where('form_session_id', $formSessionId)
                                ->where('nip', $officialData['nip'])
                                ->where('periode_jabatan', $officialData['periode_jabatan'])
                                ->first();
            }

            $documentData = array_merge($request->input('document'), [
                'vendor_id' => $vendor->id,
                'form_session_id' => $formSessionId
            ]);

            $document = Document::create($documentData);

            foreach ($request->input('officials') as $officialData) {
                $official = Official::where('form_session_id', $formSessionId)
                                ->where('nip', $officialData['nip'])
                                ->where('periode_jabatan', $officialData['periode_jabatan'])
                                ->first();

                if (!$official) {
                    throw new \Exception('Official not found for NIP: ' . $officialData['nip'] . 
                                    ' and period: ' . $officialData['periode_jabatan']);
                }

                DocumentOfficial::create([
                    'official_id' => $official->id,
                    'nomor_kontrak' => $document->nomor_kontrak,
                    'form_session_id' => $formSessionId
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Data dokumen dan pejabat berhasil disimpan',
                'data' => [
                    'document' => $document,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateDocument(Request $request, $nomor_kontrak)
    {
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'officials.*.periode_jabatan' => 'required|string|exists:officials,periode_jabatan',
            'document.nomor_kontrak' => [
                'required',
                'string',
                ValidationRule::unique('documents', 'nomor_kontrak')->ignore($nomor_kontrak, 'nomor_kontrak')
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
        $vendor = $document->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor tidak ditemukan untuk dokumen ini.');
        }

        $documentData = $request->input('document');
        $newNomorKontrak = $documentData['nomor_kontrak'];

        if ($nomor_kontrak !== $newNomorKontrak) {
            $document->update(['nomor_kontrak' => $newNomorKontrak] + $documentData);
        } else {
            $document->update($documentData);
        }

        // Update officials
        $officialsInput = $request->input('officials');
        $officialIds = [];

        foreach ($officialsInput as $officialData) {
            $official = Official::where('nip', $officialData['nip'])
                                ->where('periode_jabatan', $officialData['periode_jabatan'])
                                ->first();

            if (!$official) {
                throw new \Exception('Official tidak ditemukan untuk NIP: ' . $officialData['nip'] . ' dan periode: ' . $officialData['periode_jabatan']);
            }

            $officialIds[] = $official->id;
        }

        $document->officials()->sync($officialIds);

        DB::commit();

        $document = Document::with(['vendor', 'contracts', 'officials'])
                           ->find($newNomorKontrak);

        return response()->json([
            'message' => 'Data dokumen dan pejabat berhasil diperbarui',
            'data' => [
                'document' => $document,
                'officials' => $document->officials
            ]
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating document with officials: ' . $e->getMessage());
        return response()->json([
            'error' => 'Terjadi kesalahan saat memperbarui data',
            'detail' => $e->getMessage()
        ], 500);
    }
    }
    
    public function getSessionData($sessionId)
    {
    try {
        // Get vendor data
        $vendor = Vendor::where('form_session_id', $sessionId)->first();
        if (!$vendor) {
            throw new \Exception('Vendor data not found');
        }

        // Get officials data
        $officials = Official::where('form_session_id', $sessionId)->get();
        if ($officials->isEmpty()) {
            throw new \Exception('Officials data not found');
        }

        // Get document data
        $document = Document::where('form_session_id', $sessionId)->first();
        if (!$document) {
            throw new \Exception('Document data not found');
        }

        // Get contracts data
        $contracts = Contract::where('form_session_id', $sessionId)->get();
        if ($contracts->isEmpty()) {
            throw new \Exception('Contracts data not found');
        }

        return response()->json([
            'vendor' => $vendor,
            'officials' => $officials,
            'document' => $document,
            'contracts' => $contracts
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Gagal mengambil data',
            'message' => $e->getMessage()
        ], 500);
    }
    }

    public function getDocumentData($nomorKontrak)
    {
        try {
            // Add logging to debug the nomor_kontrak value
            Log::info('Fetching document with nomor_kontrak: ' . $nomorKontrak);

            // Get document with all related data using relationships
            $document = Document::with([
                'vendor',
                'officials',
                'contracts'
            ])->where('nomor_kontrak', $nomorKontrak)
                ->first();

            if (!$document) {
                Log::warning('Document not found for nomor_kontrak: ' . $nomorKontrak);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found with nomor_kontrak: ' . $nomorKontrak
                ], 404);
            }

            Log::info('Document found:', ['id' => $document->nomor_kontrak]);

            return response()->json($document);

        } catch (\Exception $e) {
            Log::error('Error fetching document: ' . $e->getMessage(), [
                'nomor_kontrak' => $nomorKontrak,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Gagal mengambil data',
                'message' => $e->getMessage()
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
                'image' => $imageData
            ]
        ]);
    }
}
