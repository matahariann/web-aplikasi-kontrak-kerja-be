<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Official;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule as ValidationRule;
use App\Http\Controllers\FormSessionController;
use App\Models\Images;

class DocumentController extends Controller
{
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
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
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
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
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
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
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

    public function getData()
    {
        try {
            $documents = Document::query()
                ->select([
                    'documents.id',
                    'documents.nomor_kontrak',
                    'documents.tanggal_kontrak',
                    'documents.paket_pekerjaan',
                    'documents.tahun_anggaran'
                ])
                ->with([
                    'vendor' => function ($query) { 
                        $query->select('id', 'nama_vendor', 'document_id'); 
                    },
                    'officials' => function ($query) {
                        $query->select('officials.id', 'nip', 'nama', 'jabatan', 'periode_jabatan');
                    },
                    'contracts' => function ($query) {
                        $query->select(
                            'id',
                            'document_id',
                            'jenis_kontrak',
                            'deskripsi',
                            'nilai_perkiraan_sendiri',
                            'nilai_kontral_awal',
                            'nilai_kontrak_akhir'
                        );
                    }
                ])
                ->whereNotNull('form_session_id')
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json([
                'status' => 'success',
                'data' => $documents,
                'message' => 'Data dokumen berhasil diambil'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDataDetail($id)
    {
        try {
            $document = Document::query()
                ->with([
                    'vendor' => function ($query) { 
                        $query->select(
                            'id', 
                            'document_id', 
                            'nama_vendor', 
                            'alamat_vendor',
                            'nama_pj',
                            'jabatan_pj', 
                            'npwp', 
                            'bank_vendor', 
                            'norek_vendor', 
                            'nama_rek_vendor'
                        );
                    },
                    'officials' => function ($query) {
                        $query->select(
                            'officials.id', 
                            'nip', 
                            'nama', 
                            'jabatan', 
                            'periode_jabatan', 
                            'surat_keputusan'
                        );
                    },
                    'contracts' => function ($query) {
                        $query->select(
                            'id',
                            'document_id',
                            'jenis_kontrak',
                            'deskripsi',
                            'jumlah_orang',
                            'durasi_kontrak',
                            'nilai_perkiraan_sendiri',
                            'nilai_kontral_awal',
                            'nilai_kontrak_akhir'
                        );
                    }
                ])
                ->findOrFail($id);
    
            // Format response untuk menyertakan multiple vendors
            return response()->json([
                'status' => 'success',
                'data' => [
                    'document' => $document,
                    'vendors' => $document->vendor, 
                    'officials' => $document->officials,
                    'contracts' => $document->contracts
                ],
                'message' => 'Detail dokumen berhasil diambil'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
