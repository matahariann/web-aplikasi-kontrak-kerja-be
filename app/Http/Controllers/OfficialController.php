<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\FormSession;
use App\Models\Official;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FormSessionController;

class OfficialController extends Controller
{
    public function updateSession(Request $request, $id)
    {
    try {
        $formSession = FormSession::findOrFail($id);
        
        $formSession->update([
            'temp_data' => $request->temp_data
        ]);
        
        return response()->json([
            'message' => 'Session updated successfully',
            'data' => $formSession
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to update session',
            'detail' => $e->getMessage()
        ], 500);
    }
    }

    public function updateOfficialSession($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $official = Official::findOrFail($id);
            $formSession = FormSession::findOrFail($request->form_session_id);
            
            // Dapatkan semua officials dari periode yang dipilih
            $newOfficials = Official::where('periode_jabatan', $official->periode_jabatan)->get();
    
            // Dapatkan document yang terkait dengan form session ini
            $document = Document::where('form_session_id', $formSession->id)->first();
    
            if ($document) {
                // Hapus semua relasi officials lama untuk document ini
                DB::table('documents_officials')
                    ->where('document_id', $document->id)
                    ->where('form_session_id', $formSession->id)
                    ->delete();
    
                // Tambahkan relasi dengan officials baru
                $documentsOfficialsData = $newOfficials->map(function ($official) use ($document, $formSession) {
                    return [
                        'document_id' => $document->id,
                        'official_id' => $official->id,
                        'form_session_id' => $formSession->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                })->toArray();
    
                DB::table('documents_officials')->insert($documentsOfficialsData);
            }
    
            // Hapus relasi officials lama dengan form session
            DB::table('official_form_sessions')
                ->where('form_session_id', $formSession->id)
                ->delete();
    
            // Tambah relasi officials baru dengan form session
            $officialFormSessionData = $newOfficials->map(function ($official) use ($formSession) {
                return [
                    'official_id' => $official->id,
                    'form_session_id' => $formSession->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();
    
            DB::table('official_form_sessions')->insert($officialFormSessionData);
            
            if ($request->has('temp_data')) {
                $formSession->update([
                    'temp_data' => array_merge($formSession->temp_data ?? [], [
                        'official' => $request->temp_data
                    ])
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Form session dan relasi official berhasil diperbarui',
                'data' => $newOfficials
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in updateOfficialSession', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Gagal memperbarui form session dan relasi official',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function addOfficial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'officials' => 'required|array|min:2',
            'officials.*.nip' => 'required|string',
            'officials.*.nama' => 'required|string',
            'officials.*.jabatan' => 'required|string',
            'officials.*.periode_jabatan' => 'required|string',
            'officials.*.surat_keputusan' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();

        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            // Cek apakah ada vendor untuk session ini
            $vendor = $formSession->vendor;
            if (!$vendor) {
                return response()->json([
                    'error' => 'Harap isi data vendor terlebih dahulu'
                ], 400);
            }

            $officials = collect($request->officials);
            
            // Cek duplikasi NIP
            $nips = $officials->pluck('nip');
            if ($nips->count() !== $nips->unique()->count()) {
                return response()->json([
                    'error' => 'Terdapat NIP yang sama antar pejabat'
                ], 400);
            }

            // Cek duplikasi NIP dan periode
            foreach ($officials as $officialData) {
                $exists = Official::where('nip', $officialData['nip'])
                                ->where('periode_jabatan', $officialData['periode_jabatan'])
                                ->exists();
                if ($exists) {
                    return response()->json([
                        'error' => "Kombinasi NIP {$officialData['nip']} dan Periode Jabatan sudah ada"
                    ], 400);
                }
            }

            // Hapus relasi official lama dari session ini
            $formSession->officials()->detach();

            // Simpan semua official baru
            $savedOfficials = collect();
            foreach ($officials as $officialData) {
                $official = Official::create([
                    'nip' => $officialData['nip'],
                    'nama' => $officialData['nama'],
                    'jabatan' => $officialData['jabatan'],
                    'periode_jabatan' => $officialData['periode_jabatan'],
                    'surat_keputusan' => $officialData['surat_keputusan'] ?? null
                ]);
                
                // Tambahkan relasi ke form session melalui pivot
                $formSession->officials()->attach($official->id);
                
                $savedOfficials->push($official);
            }

            // Update documents_officials jika sudah ada document
            $document = Document::where('form_session_id', $formSession->id)->first();
            if ($document) {
                // Hapus relasi lama
                $document->officials()->detach();
                
                // Tambah relasi baru
                foreach ($savedOfficials as $official) {
                    $document->officials()->attach($official->id, [
                        'form_session_id' => $formSession->id
                    ]);
                }
            }

            // Update session data
            $formSession->update([
                'current_step' => 'document',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'officials' => $request->officials
                ])
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Data pejabat berhasil disimpan',
                'data' => [
                    'officials' => $savedOfficials,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
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
        $formSessionController = new FormSessionController();
        $formSession = $formSessionController->getOrCreateFormSession();
        $official = Official::findOrFail($id);

        // Cek apakah official terkait dengan session ini melalui pivot
        $isOfficialInSession = $formSession->officials()
            ->where('officials.id', $id)
            ->exists();

        if (!$isOfficialInSession) {
            return response()->json([
                'error' => 'Data pejabat tidak ditemukan dalam session ini'
            ], 404);
        }

        // Cek duplikasi NIP dan periode
        if (($official->nip !== $request->nip) || ($official->periode_jabatan !== $request->periode_jabatan)) {
            $exists = Official::where('nip', $request->nip)
                            ->where('periode_jabatan', $request->periode_jabatan)
                            ->where('id', '!=', $id)
                            ->exists();
            if ($exists) {
                return response()->json([
                    'error' => 'Kombinasi NIP dan Periode Jabatan sudah ada'
                ], 400);
            }
        }

        // Update official data
        $official->update($request->all());

        // Update temp data in session
        $formSession->update([
            'temp_data' => array_merge($formSession->temp_data ?? [], [
                'official' => $request->all()
            ])
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Data pejabat berhasil diperbarui',
            'data' => [
                'official' => $official,
                'session' => [
                    'id' => $formSession->id,
                    'current_step' => $formSession->current_step
                ]
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error in updateOfficial', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Terjadi kesalahan saat memperbarui pejabat',
            'detail' => $e->getMessage()
        ], 500);
    }
    }

    public function getOfficialData()
    {
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            $officials = $formSession->officials;
            
            return response()->json([
                'data' => [
                    'officials' => $officials,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step,
                        'temp_data' => $formSession->temp_data['official'] ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data pejabat',
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
}
