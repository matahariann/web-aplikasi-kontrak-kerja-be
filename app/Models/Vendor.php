<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';
    protected $fillable = [
        'nama_vendor',
        'alamat_vendor',
        'nama_pj',
        'jabatan_pj',
        'npwp',
        'bank_vendor',
        'norek_vendor',
        'nama_rek_vendor',
        'document_id',
        'form_session_id',
    ];

    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }

    public function formSession()
    {
        return $this->belongsTo(FormSession::class, 'form_session_id', 'id');
    }
}
