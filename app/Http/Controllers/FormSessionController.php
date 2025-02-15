<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentOfficial;
use App\Models\FormSession;
use App\Models\Official;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FormSessionController extends Controller
{
    public function getOrCreateFormSession()
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

    public function completeForm()
    {
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            // Validasi semua data sudah terisi
            if (!$formSession->vendor || !$formSession->officials->count() || 
                !$formSession->document || !$formSession->contract) {
                return response()->json([
                    'error' => 'Semua form harus diisi terlebih dahulu'
                ], 400);
            }
    
            // Update session sebagai completed dan hapus temp_data
            $formSession->update([
                'is_completed' => true,
                'temp_data' => null
            ]);
    
            return response()->json([
                'message' => 'Form berhasil diselesaikan',
                'data' => [
                    'session_id' => $formSession->id
                ]
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyelesaikan form',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function clearFormSession()
    {
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            if ($formSession) {
                DB::beginTransaction();
                
                // Hapus semua data terkait secara berurutan
                // untuk menghindari constraint violation
                $formSession->contract()->delete();
                
                // Hapus relasi document-official terlebih dahulu
                DocumentOfficial::where('form_session_id', $formSession->id)->delete();
                
                $formSession->document()->delete();
                $formSession->officials()->delete();
                $formSession->vendor()->delete();
                
                // Terakhir hapus form session
                $formSession->delete();
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Session form berhasil dihapus'
                ]);
            }
            
            return response()->json([
                'message' => 'Tidak ada session aktif'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus session',
                'detail' => $e->getMessage()
            ], 500);
        }
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
}
