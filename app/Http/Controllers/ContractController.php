<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentOfficial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FormSessionController;

class ContractController extends Controller
{
    public function addContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract' => 'required|array',
            'contract.jenis_kontrak' => 'required|string',
            'contract.deskripsi' => 'required|string',
            'contract.jumlah_orang' => 'required|integer',
            'contract.durasi_kontrak' => 'required|integer',
            'contract.nilai_perkiraan_sendiri' => 'required|numeric',
            'contract.nilai_kontral_awal' => 'required|numeric',
            'contract.nilai_kontrak_akhir' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            // Cek apakah document sudah ada
            $document = $formSession->document;
            if (!$document) {
                return response()->json([
                    'error' => 'Harap isi data dokumen terlebih dahulu'
                ], 400);
            }
    
            // Simpan data contract
            $contractData = array_merge($request->input('contract'), [
                'document_id' => $document->id,  // Menggunakan document id
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
            'contracts' => 'required|array',
            'contracts.*.jenis_kontrak' => 'required|string',
            'contracts.*.deskripsi' => 'required|string',
            'contracts.*.jumlah_orang' => 'required|integer',
            'contracts.*.durasi_kontrak' => 'required|integer',
            'contracts.*.nilai_perkiraan_sendiri' => 'required|numeric',
            'contracts.*.nilai_kontral_awal' => 'required|numeric',
            'contracts.*.nilai_kontrak_akhir' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            // Find the document associated with the first contract
            $document = Document::whereHas('contracts', function($query) use ($id) {
                $query->where('id', $id);
            })->first();
    
            if (!$document) {
                throw new \Exception('Dokumen tidak ditemukan');
            }
    
            $contracts = collect($request->input('contracts'));
            
            // Separate existing and new contracts
            $existingContracts = $contracts->filter(fn($c) => isset($c['id']) && is_string($c['id']) && !empty($c['id']));
            $newContracts = $contracts->filter(fn($c) => !isset($c['id']) || !is_string($c['id']) || empty($c['id']));
    
            // Update existing contracts
            foreach ($existingContracts as $contractData) {
                $contract = Contract::where('id', $contractData['id'])
                                ->where('document_id', $document->id)
                                ->where('form_session_id', $formSession->id)
                                ->first();
                
                if ($contract) {
                    $contract->update([
                        'jenis_kontrak' => $contractData['jenis_kontrak'],
                        'deskripsi' => $contractData['deskripsi'],
                        'jumlah_orang' => $contractData['jumlah_orang'],
                        'durasi_kontrak' => $contractData['durasi_kontrak'],
                        'nilai_perkiraan_sendiri' => $contractData['nilai_perkiraan_sendiri'],
                        'nilai_kontral_awal' => $contractData['nilai_kontral_awal'],
                        'nilai_kontrak_akhir' => $contractData['nilai_kontrak_akhir'],
                    ]);
                }
            }
    
            // Add new contracts
            foreach ($newContracts as $contractData) {
                Contract::create([
                    'jenis_kontrak' => $contractData['jenis_kontrak'],
                    'deskripsi' => $contractData['deskripsi'],
                    'jumlah_orang' => $contractData['jumlah_orang'],
                    'durasi_kontrak' => $contractData['durasi_kontrak'],
                    'nilai_perkiraan_sendiri' => $contractData['nilai_perkiraan_sendiri'],
                    'nilai_kontral_awal' => $contractData['nilai_kontral_awal'],
                    'nilai_kontrak_akhir' => $contractData['nilai_kontrak_akhir'],
                    'document_id' => $document->id, 
                    'form_session_id' => $formSession->id
                ]);
            }
    
            // Update temp data di session
            $formSession->update([
                'temp_data' => array_merge($formSession->temp_data ?? [], [
                    'contracts' => $request->input('contracts')
                ])
            ]);
    
            DB::commit();
    
            // Get all updated contracts for response
            $updatedContracts = Contract::where('form_session_id', $formSession->id)
                                        ->where('document_id', $document->id)
                                        ->get();
    
            return response()->json([
                'message' => 'Data kontrak berhasil diperbarui',
                'data' => [
                    'contracts' => $updatedContracts,
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

    public function deleteContract($id)
    {
        DB::beginTransaction();
        try {
            $formSessionController = new FormSessionController();
            $formSession = $formSessionController->getOrCreateFormSession();
            
            $contract = Contract::where('id', $id)
                              ->where('form_session_id', $formSession->id)
                              ->firstOrFail();
    
            $contract->delete();
    
            // Update temp data di session jika perlu
            if (isset($formSession->temp_data['contracts']) && is_array($formSession->temp_data['contracts'])) {
                $contracts = collect($formSession->temp_data['contracts'])
                    ->filter(function($contract) use ($id) {
                        return isset($contract['id']) && $contract['id'] !== $id;
                    })
                    ->values()
                    ->all();
                
                $tempData = $formSession->temp_data;
                $tempData['contracts'] = $contracts;
                
                $formSession->update([
                    'temp_data' => $tempData
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Data kontrak berhasil dihapus',
                'data' => [
                    'deleted_id' => $id
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat menghapus kontrak',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    public function getContractData()
    {
    try {
        $formSessionController = new FormSessionController();
        $formSession = $formSessionController->getOrCreateFormSession();
        $contracts = Contract::where('form_session_id', $formSession->id)->get();
        
        return response()->json([
            'data' => [
                'contracts' => $contracts,
                'session' => [
                    'id' => $formSession->id,
                    'current_step' => $formSession->current_step,
                    'temp_data' => $formSession->temp_data['contracts'] ?? null
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
}
