<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\DocumentOfficial;
use App\Models\FormSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContractController extends Controller
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
    public function addContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract' => 'required|array',
            'contract.jenis_kontrak' => 'required|string',
            'contract.deskripsi' => 'required|string',
            'contract.jumlah_orang' => 'required|integer',
            'contract.durasi_kontrak' => 'required|integer',
            'contract.nilai_kontral_awal' => 'required|numeric',
            'contract.nilai_kontrak_akhir' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSession = $this->getOrCreateFormSession();
            
            // Cek apakah document sudah ada
            $document = $formSession->document;
            if (!$document) {
                return response()->json([
                    'error' => 'Harap isi data dokumen terlebih dahulu'
                ], 400);
            }
    
            // Simpan data contract
            $contractData = array_merge($request->input('contract'), [
                'nomor_kontrak' => $document->nomor_kontrak,
                'form_session_id' => $formSession->id
            ]);
    
            $contract = Contract::create($contractData);
    
            // Update session data
            $formSession->update([
                'current_step' => 'completed',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'contract' => $request->input('contract')
                ])
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil disimpan',
                'data' => [
                    'contract' => $contract,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan data kontrak',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateContract(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contract' => 'required|array',
            'contract.jenis_kontrak' => 'required|string',
            'contract.deskripsi' => 'required|string',
            'contract.jumlah_orang' => 'required|integer',
            'contract.durasi_kontrak' => 'required|integer',
            'contract.nilai_kontral_awal' => 'required|numeric',
            'contract.nilai_kontrak_akhir' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSession = $this->getOrCreateFormSession();
            $contract = Contract::where('id', $id)
                              ->where('form_session_id', $formSession->id)
                              ->firstOrFail();
    
            // Update contract
            $contract->update($request->input('contract'));
    
            // Update temp data di session
            $formSession->update([
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'contract' => $request->input('contract')
                ])
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil diperbarui',
                'data' => [
                    'contract' => $contract,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui kontrak',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getContractData()
    {
        try {
            $formSession = $this->getOrCreateFormSession();
            $contract = $formSession->contract;
            
            return response()->json([
                'data' => [
                    'contract' => $contract,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step,
                        'temp_data' => $formSession->temp_data['contract'] ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data kontrak',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function completeForm(Request $request)
    {
        try {
            $formSession = $this->getOrCreateFormSession();
            
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
            $formSession = $this->getOrCreateFormSession();
            
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
