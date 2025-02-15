<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FormSessionController;

class VendorController extends Controller
{
    public function addVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendors' => 'required|array',
            'vendors.nama_vendor' => 'required|string',
            'vendors.alamat_vendor' => 'required|string',
            'vendors.nama_pj' => 'required|string',
            'vendors.jabatan_pj' => 'required|string',
            'vendors.npwp' => 'required|string',
            'vendors.bank_vendor' => 'required|string',
            'vendors.norek_vendor' => 'required|string',
            'vendors.nama_rek_vendor' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            $vendor = Vendor::create(array_merge($request->input('vendors'), [
                'form_session_id' => $formSession->id
            ]));
            
            $formSession->update([
                'current_step' => 'official',
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'vendors' => $request->input('vendors')
                ])
            ]);
            
            DB::commit();
    
            return response()->json([
                'message' => 'Data vendor berhasil disimpan',
                'data' => [
                    'vendors' => $vendor,
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
        // Wrap single vendor object in array if needed
        $vendorsData = isset($request->vendors[0]) ? $request->vendors : [$request->vendors];
    
        $validator = Validator::make(['vendors' => $vendorsData], [
            'vendors' => 'required|array',
            'vendors.*.nama_vendor' => 'required|string',
            'vendors.*.alamat_vendor' => 'required|string',
            'vendors.*.nama_pj' => 'required|string',
            'vendors.*.jabatan_pj' => 'required|string',
            'vendors.*.npwp' => 'required|string',
            'vendors.*.bank_vendor' => 'required|string',
            'vendors.*.norek_vendor' => 'required|string',
            'vendors.*.nama_rek_vendor' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            // Cek apakah sudah ada document untuk session ini
            $existingDocument = Document::where('form_session_id', $formSession->id)->first();
            
            $vendors = collect($vendorsData);
            
            // Separate existing and new vendors
            $existingVendors = $vendors->filter(fn($v) => isset($v['id']) && !empty($v['id']));
            $newVendors = $vendors->filter(fn($v) => !isset($v['id']) || empty($v['id']));
    
            // Update existing vendors
            foreach ($existingVendors as $vendorData) {
                $vendor = Vendor::where('id', $vendorData['id'])
                               ->where('form_session_id', $formSession->id)
                               ->first();
                
                if ($vendor) {
                    $vendor->update($vendorData);
                }
            }
    
            // Add new vendors
            foreach ($newVendors as $vendorData) {
                $newVendorData = array_merge($vendorData, [
                    'form_session_id' => $formSession->id,
                    'document_id' => $existingDocument ? $existingDocument->id : null // Tambahkan document_id jika ada
                ]);
                
                Vendor::create($newVendorData);
            }
            
            // Update temp data
            $formSession->update([
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'vendors' => $vendorsData
                ])
            ]);
            
            DB::commit();
            
            $updatedVendors = Vendor::where('form_session_id', $formSession->id)->get();
            
            return response()->json([
                'message' => 'Data vendor berhasil diperbarui',
                'data' => [
                    'vendors' => $updatedVendors,
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

    public function deleteVendor(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            $vendor = Vendor::where('id', $id)
                          ->where('form_session_id', $formSession->id)
                          ->firstOrFail();
    
            $vendor->delete();
    
            // Update temp data di session
            if (isset($formSession->temp_data['vendors']) && is_array($formSession->temp_data['vendors'])) {
                $vendors = collect($formSession->temp_data['vendors'])
                    ->filter(function($vendor) use ($id) {
                        return isset($vendor['id']) && $vendor['id'] !== $id;
                    })
                    ->values()
                    ->all();
                
                $tempData = $formSession->temp_data;
                $tempData['vendors'] = $vendors;
                
                $formSession->update([
                    'temp_data' => $tempData
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data vendor berhasil dihapus',
                'data' => [
                    'deleted_id' => $id
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus vendor',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function getVendorData()
    {
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            $vendors = Vendor::where('form_session_id', $formSession->id)->get();
            
            return response()->json([
                'data' => [
                    'vendors' => $vendors,
                    'session' => [
                        'id' => $formSession->id,
                        'current_step' => $formSession->current_step,
                        'temp_data' => $formSession->temp_data['vendors'] ?? null
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
