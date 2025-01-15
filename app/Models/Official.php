<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    use HasFactory;

    protected $table = 'officials';
    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'periode_jabatan',
        'surat_keputusan',
        'form_session_id',
    ];

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'documents_officials', 'official_id', 'nomor_kontrak')
                    ->withTimestamps();
    }
}
