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
        'document_id',
        'form_session_id',
    ];
    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function formSession()
    {
        return $this->belongsTo(FormSession::class, 'form_session_id', 'id');
    }
}
