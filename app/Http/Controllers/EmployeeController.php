<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentOfficial;
use App\Models\Images;
use App\Models\Official;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function getAuthenticatedEmployee()
    {
    $user = Auth::user();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'employee' => [ 
                'nip' => $user->nip,
                'nama' => $user->nama,
                'email' => $user->email,
                'noTelp' => $user->no_telp, 
                'alamat' => $user->alamat
            ]
        ]
    ]);
    }

    public function getData()
    {
        try {
            $documents = Document::with([
                'vendor:id,nama_vendor',
                'officials:officials.id,nip,nama,jabatan,periode_jabatan',
                'contracts:id,nomor_kontrak,jenis_kontrak,deskripsi,nilai_kontral_awal,nilai_kontrak_akhir'
            ])
            ->select(
                'nomor_kontrak',
                'tanggal_kontrak',
                'paket_pekerjaan',
                'tahun_anggaran',
                'vendor_id'
            )
            ->orderBy('created_at', 'desc')
            ->get();
    
            if ($documents->isEmpty()) {
                return response()->json([]);
            }
    
            return response()->json($documents);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengambil data dokumen',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    public function getDataDetail($nomorKontrak)
    {
        try {
            $document = Document::with([
                'vendor',
                'officials',
                'contracts'
            ])
            ->where('nomor_kontrak', $nomorKontrak)
            ->firstOrFail();
    
            if (!$document) {
                return response()->json([
                    'error' => 'Dokumen tidak ditemukan'
                ], 404);
            }
    
            return response()->json([
                'document' => $document,
                'vendor' => $document->vendor,
                'officials' => $document->officials,
                'contracts' => $document->contracts
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengambil detail dokumen',
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
}
