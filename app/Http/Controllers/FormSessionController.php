<?php

namespace App\Http\Controllers;

use App\Models\DocumentOfficial;
use App\Models\FormSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
}
