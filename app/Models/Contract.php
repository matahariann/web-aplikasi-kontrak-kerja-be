<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';
    protected $fillable = [
        'jenis_kontrak',
        'deskripsi',
        'jumlah_orang',
        'durasi_kontrak',
        'nilai_kontral_awal',
        'nilai_kontrak_akhir',
        'nomor_kontrak',
        'form_session_id',
    ];
    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class, 'nomor_kontrak', 'nomor_kontrak');
    }
}
