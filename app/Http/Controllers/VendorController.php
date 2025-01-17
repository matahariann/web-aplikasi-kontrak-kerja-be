<?php

namespace App\Http\Controllers;

use App\Models\FormSession;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorController extends Controller
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
            $formSession = $this->getOrCreateFormSession();
            
            // Simpan data vendor
            $vendor = Vendor::create(array_merge(
                $request->all(),
                ['form_session_id' => $formSession->id]
            ));
            
            // Update session
            $formSession->update([
                'current_step' => 'official',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'vendor' => $request->all()
                ])
            ]);
            
            DB::commit();
    
            return response()->json([
                'message' => 'Data vendor berhasil disimpan',
                'data' => [
                    'vendor' => $vendor,
                    'session_id' => $formSession->id
                ]
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menambahkan vendor',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVendor(Request $request)
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
            $formSession = $this->getOrCreateFormSession();
            
            // Update temp data
            $formSession->update([
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'vendor' => $request->all()
                ])
            ]);

            // Update vendor data
            $vendor = $formSession->vendor;
            if (!$vendor) {
                throw new \Exception('Data vendor tidak ditemukan');
            }
            
            $vendor->update($request->all());
            
            DB::commit();
            
            return response()->json([
                'message' => 'Data vendor berhasil diperbarui',
                'data' => [
                    'vendor' => $vendor,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui vendor',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function getVendorData()
    {
        try {
            $formSession = $this->getOrCreateFormSession();
            $vendor = $formSession->vendor;
            
            return response()->json([
                'data' => [
                    'vendor' => $vendor,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step,
                        'temp_data' => $formSession->temp_data['vendor'] ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data vendor',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
