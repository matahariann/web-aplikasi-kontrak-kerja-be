<?php

namespace App\Http\Controllers;

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
    public function addOfficial(Request $request)
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
            
            // Cek apakah ada vendor untuk session ini
            $vendor = $formSession->vendor;
            if (!$vendor) {
                return response()->json([
                    'error' => 'Harap isi data vendor terlebih dahulu'
                ], 400);
            }

            // Cek duplikasi NIP dan periode
            $exists = Official::where('nip', $request->nip)
                            ->where('periode_jabatan', $request->periode_jabatan)
                            ->exists();
            if ($exists) {
                return response()->json([
                    'error' => 'Kombinasi NIP dan Periode Jabatan sudah ada'
                ], 400);
            }

            // Simpan data official
            $official = Official::create(array_merge(
                $request->all(),
                ['form_session_id' => $formSession->id]
            ));

            // Update session data
            $formSession->update([
                'current_step' => 'document',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'official' => $request->all()
                ])
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data pejabat berhasil disimpan',
                'data' => [
                    'official' => $official,
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
