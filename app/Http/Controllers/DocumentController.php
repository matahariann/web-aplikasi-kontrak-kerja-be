<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\FormSession;
use App\Models\Official;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule as ValidationRule;

class DocumentController extends Controller
{
    private function getOrCreateFormSession()
    {
        $user = Auth::user();
        
        // Cari session aktif
        $activeSession = $user->formSessions()
            ->where('is_completed', false)
            ->latest()
            ->first();
        
        if (!$activeSession) {
            // Buat session baru jika tidak ada
            $activeSession = FormSession::create([
                'id' => Str::uuid(),
                'nip' => $user->nip,
                'current_step' => 'vendor',
                'is_completed' => false
            ]);
        }
        
        return $activeSession;
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
            'document.nomor_ukn' => 'required|string',
            'document.tanggal_ukn' => 'required|date',
            'document.tanggal_undangan_ukn' => 'required|date',
            'document.nomor_ba_ekn' => 'required|string',
            'document.tanggal_ba_ekn' => 'required|date',
            'document.nomor_pppb' => 'required|string',
            'document.tanggal_pppb' => 'required|date',
            'document.nomor_lppb' => 'required|string',
            'document.tanggal_lppb' => 'required|date',
            'document.nomor_ba_stp' => 'required|string',
            'document.tanggal_ba_stp' => 'required|date',
            'document.nomor_ba_pem' => 'required|string',
            'document.tanggal_ba_pem' => 'required|date',
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
            $formSession = $this->getOrCreateFormSession();
            
            // Cek vendor tetap sama
            $vendors = Vendor::where('form_session_id', $formSession->id)->get();
            if ($vendors->isEmpty()) {
                return response()->json([
                    'error' => 'Harap isi data vendor terlebih dahulu'
                ], 400);
            }
    
            // Cek officials melalui pivot table
            $officials = [];
            foreach ($request->input('officials') as $officialData) {
                $official = Official::whereHas('formSessions', function($query) use ($formSession, $officialData) {
                    $query->where('form_sessions.id', $formSession->id)
                          ->where('officials.nip', $officialData['nip'])
                          ->where('officials.periode_jabatan', $officialData['periode_jabatan']);
                })->first();
    
                if (!$official) {
                    return response()->json([
                        'error' => 'Official dengan NIP ' . $officialData['nip'] . ' tidak terkait dengan session ini'
                    ], 400);
                }
                
                $officials[] = $official->id;
            }
    
            // Simpan document
            $documentData = array_merge($request->input('document'), [
                'form_session_id' => $formSession->id
            ]);
    
            $document = Document::create($documentData);
    
            // Update vendor
            Vendor::where('form_session_id', $formSession->id)
                  ->update(['document_id' => $document->id]);
    
            // Attach officials dengan form_session_id
            $pivotData = array_fill(0, count($officials), [
                'form_session_id' => $formSession->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $document->officials()->attach(array_combine($officials, $pivotData));
    
            // Update session seperti biasa
            $formSession->update([
                'current_step' => 'contract',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'document' => $request->input('document'),
                    'officials' => $request->input('officials')
                ])
            ]);
    
            DB::commit();
    
            // Load relasi dengan kondisi session
            $document->load(['vendor', 'officials' => function($query) use ($formSession) {
                $query->wherePivot('form_session_id', $formSession->id);
            }]);
    
            return response()->json([
                'message' => 'Data dokumen berhasil disimpan',
                'data' => [
                    'document' => $document,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDocument(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:1',
            'officials.*.nip' => 'required|string|exists:officials,nip',
            'officials.*.periode_jabatan' => 'required|string|exists:officials,periode_jabatan',
            'document' => 'required|array',
            'document.nomor_kontrak' => [
                'required',
                'string',
                ValidationRule::unique('documents', 'nomor_kontrak')->ignore($id)
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
            'document.nomor_ukn' => 'required|string',
            'document.tanggal_ukn' => 'required|date',
            'document.tanggal_undangan_ukn' => 'required|date',
            'document.nomor_ba_ekn' => 'required|string',
            'document.tanggal_ba_ekn' => 'required|date',
            'document.nomor_pppb' => 'required|string',
            'document.tanggal_pppb' => 'required|date',
            'document.nomor_lppb' => 'required|string',
            'document.tanggal_lppb' => 'required|date',
            'document.nomor_ba_stp' => 'required|string',
            'document.tanggal_ba_stp' => 'required|date',
            'document.nomor_ba_pem' => 'required|string',
            'document.tanggal_ba_pem' => 'required|date',
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
            $formSession = $this->getOrCreateFormSession();
            $document = Document::where('id', $id)
                              ->where('form_session_id', $formSession->id)
                              ->firstOrFail();
    
            // Update document data
            $documentData = $request->input('document');
            $document->update($documentData);
    
            // Update officials dengan memperhatikan session
            $officials = [];
            foreach ($request->input('officials') as $officialData) {
                $official = Official::whereHas('formSessions', function($query) use ($formSession, $officialData) {
                    $query->where('form_sessions.id', $formSession->id)
                          ->where('officials.nip', $officialData['nip'])
                          ->where('officials.periode_jabatan', $officialData['periode_jabatan']);
                })->first();
    
                if ($official) {
                    $officials[] = $official->id;
                }
            }
    
            // Sync officials dengan mempertahankan form_session_id
            $pivotData = array_fill(0, count($officials), [
                'form_session_id' => $formSession->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $document->officials()->wherePivot('form_session_id', $formSession->id)->detach();
            $document->officials()->attach(array_combine($officials, $pivotData));
    
            // Update session data
            $formSession->update([
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'document' => $request->input('document'),
                    'officials' => $request->input('officials')
                ])
            ]);
    
            DB::commit();
    
            // Load relasi dengan kondisi session
            $document->load(['vendor', 'officials' => function($query) use ($formSession) {
                $query->wherePivot('form_session_id', $formSession->id);
            }]);
    
            return response()->json([
                'message' => 'Data dokumen berhasil diperbarui',
                'data' => [
                    'document' => $document,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }


    public function getDocumentData()
    {
        try {
            $formSession = $this->getOrCreateFormSession();
            $document = Document::where('form_session_id', $formSession->id)->first();
            
            return response()->json([
                'data' => [
                    'document' => $document,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step,
                        'temp_data' => $formSession->temp_data['document'] ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
