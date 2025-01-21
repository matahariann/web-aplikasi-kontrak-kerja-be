<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Images;
use Illuminate\Support\Facades\Auth;

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
            // Query utama dengan eager loading yang dioptimasi
            $documents = Document::query()
                ->select([
                    'documents.id',
                    'documents.nomor_kontrak',
                    'documents.tanggal_kontrak',
                    'documents.paket_pekerjaan',
                    'documents.tahun_anggaran'
                    // Hapus vendor_id karena sekarang one-to-many
                ])
                ->with([
                    'vendor' => function ($query) {  // Ubah ke vendor (plural)
                        $query->select('id', 'nama_vendor', 'document_id'); // Tambah document_id
                    },
                    'officials' => function ($query) {
                        $query->select('officials.id', 'nip', 'nama', 'jabatan', 'periode_jabatan');
                    },
                    'contracts' => function ($query) {
                        $query->select(
                            'id',
                            'document_id',
                            'jenis_kontrak',
                            'deskripsi',
                            'nilai_perkiraan_sendiri',
                            'nilai_kontral_awal',
                            'nilai_kontrak_akhir'
                        );
                    }
                ])
                ->whereNotNull('form_session_id')
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json([
                'status' => 'success',
                'data' => $documents,
                'message' => 'Data dokumen berhasil diambil'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDataDetail($id)
    {
        try {
            $document = Document::query()
                ->with([
                    'vendor' => function ($query) {  // Ubah ke vendor (plural)
                        $query->select(
                            'id', 
                            'document_id', // Tambah document_id
                            'nama_vendor', 
                            'alamat_vendor',
                            'nama_pj',
                            'jabatan_pj', 
                            'npwp', 
                            'bank_vendor', 
                            'norek_vendor', 
                            'nama_rek_vendor'
                        );
                    },
                    'officials' => function ($query) {
                        $query->select(
                            'officials.id', 
                            'nip', 
                            'nama', 
                            'jabatan', 
                            'periode_jabatan', 
                            'surat_keputusan'
                        );
                    },
                    'contracts' => function ($query) {
                        $query->select(
                            'id',
                            'document_id',
                            'jenis_kontrak',
                            'deskripsi',
                            'jumlah_orang',
                            'durasi_kontrak',
                            'nilai_perkiraan_sendiri',
                            'nilai_kontral_awal',
                            'nilai_kontrak_akhir'
                        );
                    }
                ])
                ->findOrFail($id);
    
            // Format response untuk menyertakan multiple vendors
            return response()->json([
                'status' => 'success',
                'data' => [
                    'document' => $document,
                    'vendors' => $document->vendor, // Ubah ke plural untuk konsistensi
                    'officials' => $document->officials,
                    'contracts' => $document->contracts
                ],
                'message' => 'Detail dokumen berhasil diambil'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail dokumen',
                'error' => $e->getMessage()
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
