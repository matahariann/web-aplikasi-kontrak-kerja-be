<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\FormSession;
use App\Models\Official;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OfficialController extends Controller
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
            
            // Log untuk debugging
            Log::info('Updating official session', [
                'official_id' => $id,
                'form_session_id' => $request->form_session_id,
                'official_data' => $official->toArray()
            ]);
    
            // Cek apakah form_session_id valid
            if (!$request->form_session_id) {
                throw new \Exception('Form session ID tidak valid');
            }
            
            // Cek apakah form session exists
            $formSession = FormSession::findOrFail($request->form_session_id);
            
            // Cari dan simpan official lama di session yang dituju
            $oldOfficial = Official::where('form_session_id', $request->form_session_id)
                   ->where('jabatan', $official->jabatan)
                   ->where('id', '!=', $id)
                   ->first();
    
            Log::info('Old official found', [
                'old_official' => $oldOfficial ? $oldOfficial->toArray() : null
            ]);
    
            if ($oldOfficial) {
                // Update form_session_id menjadi null
                $oldOfficial->update(['form_session_id' => null]);
    
                // Cari document untuk session ini
                $document = Document::where('form_session_id', $request->form_session_id)->first();
                
                Log::info('Document found', [
                    'document' => $document ? $document->toArray() : null
                ]);
    
                if ($document) {
                    // Hapus relasi lama di pivot table
                    $deleted = DB::table('documents_officials')
                        ->where('official_id', $oldOfficial->id)
                        ->where('nomor_kontrak', $document->nomor_kontrak)
                        ->delete();
    
                    Log::info('Deleted old pivot records', ['count' => $deleted]);
    
                    // Cek apakah relasi sudah ada sebelum insert
                    $existingPivot = DB::table('documents_officials')
                        ->where('official_id', $id)
                        ->where('nomor_kontrak', $document->nomor_kontrak)
                        ->exists();
    
                    if (!$existingPivot) {
                        // Tambah relasi baru
                        DB::table('documents_officials')->insert([
                            'official_id' => $id,
                            'nomor_kontrak' => $document->nomor_kontrak,
                            'form_session_id' => $request->form_session_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
            
            // Update form_session_id official yang dipilih
            $official->update([
                'form_session_id' => $request->form_session_id
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Form session ID dan relasi dokumen berhasil diperbarui',
                'data' => $official
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in updateOfficialSession', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Gagal memperbarui form session ID dan relasi dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    public function addOfficial(Request $request)
    {
        // Validasi request harus berupa array dari officials
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
            $formSession = $this->getOrCreateFormSession();
            
            // Cek apakah ada vendor untuk session ini
            $vendor = $formSession->vendor;
            if (!$vendor) {
                return response()->json([
                    'error' => 'Harap isi data vendor terlebih dahulu'
                ], 400);
            }

            $officials = collect($request->officials);
            
            // Cek duplikasi NIP di antara officials yang akan disimpan
            $nips = $officials->pluck('nip');
            if ($nips->count() !== $nips->unique()->count()) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Terdapat NIP yang sama antar pejabat'
                ], 400);
            }

            // Cek duplikasi NIP dan periode untuk setiap official
            foreach ($officials as $officialData) {
                $exists = Official::where('nip', $officialData['nip'])
                                ->where('periode_jabatan', $officialData['periode_jabatan'])
                                ->exists();
                if ($exists) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Kombinasi NIP {$officialData['nip']} dan Periode Jabatan sudah ada"
                    ], 400);
                }
            }

            // Hapus semua official lama dalam session ini
            Official::where('form_session_id', $formSession->id)
                   ->update(['form_session_id' => null]);

            // Simpan semua official baru
            $savedOfficials = collect();
            foreach ($officials as $officialData) {
                $official = Official::create([
                    'nip' => $officialData['nip'],
                    'nama' => $officialData['nama'],
                    'jabatan' => $officialData['jabatan'],
                    'periode_jabatan' => $officialData['periode_jabatan'],
                    'surat_keputusan' => $officialData['surat_keputusan'] ?? null,
                    'form_session_id' => $formSession->id
                ]);
                $savedOfficials->push($official);
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
            $formSession = $this->getOrCreateFormSession();
            $official = Official::findOrFail($id);

            // Validasi official belongs to current session
            if ($official->form_session_id !== $formSession->id) {
                return response()->json([
                    'error' => 'Data pejabat tidak ditemukan dalam session ini'
                ], 404);
            }
    
            // Cek duplikasi NIP dan periode
            if (($official->nip !== $request->nip) || ($official->periode_jabatan !== $request->periode_jabatan)) {
                $exists = Official::where('nip', $request->nip)
                                ->where('periode_jabatan', $request->periode_jabatan)
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
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui pejabat',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function getOfficialData()
    {
        try {
            $formSession = $this->getOrCreateFormSession();
            $officials = $formSession->officials; // Menggunakan relasi hasMany
            
            return response()->json([
                'data' => [
                    'officials' => $officials, // Mengembalikan array of officials
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
