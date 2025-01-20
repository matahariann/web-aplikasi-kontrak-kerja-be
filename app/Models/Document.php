<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';
    protected $fillable = [
        'nomor_kontrak',
        'tanggal_kontrak',
        'paket_pekerjaan',
        'tahun_anggaran',
        'nomor_pp',
        'tanggal_pp',
        'nomor_hps',
        'tanggal_hps',
        'tanggal_mulai',
        'tanggal_selesai',
        'nomor_pph1',
        'tanggal_pph1',
        // 'nomor_pph2',
        // 'tanggal_pph2',
        'nomor_ukn',
        'tanggal_ukn',
        'tanggal_undangan_ukn',
        'nomor_ba_ekn',
        'tanggal_ba_ekn',
        'nomor_pppb',
        'tanggal_pppb',
        'nomor_lppb',
        'tanggal_lppb',
        'nomor_ba_stp',
        'tanggal_ba_stp',
        'nomor_ba_pem',
        'tanggal_ba_pem',
        'nomor_dipa',
        'tanggal_dipa',
        'kode_kegiatan',
        'vendor_id',
        'form_session_id',
    ];

    protected $guarded = [];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'document_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function officials()
    {
        return $this->belongsToMany(Official::class, 'documents_officials', 'document_id', 'official_id')
                    ->withTimestamps();
    }

    public function formSession()
    {
        return $this->belongsTo(FormSession::class, 'form_session_id', 'id');
    }
}
