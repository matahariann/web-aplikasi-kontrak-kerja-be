<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentOfficial extends Model
{
    use HasFactory;

    protected $table = 'documents_officials';

    protected $fillable = [
        'nip',
        'nomor_kontrak',
    ];
    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class, 'nomor_kontrak', 'nomor_kontrak');
    }

    public function official()
    {
        return $this->belongsTo(Official::class, 'nip', 'nip');
    }
}
