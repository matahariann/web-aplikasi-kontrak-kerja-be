<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentOfficial extends Model
{
    use HasFactory;

    protected $table = 'documents_officials';

    protected $fillable = [
        'official_id',
        'nomor_kontrak',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'nomor_kontrak', 'nomor_kontrak');
    }

    public function official()
    {
        return $this->belongsTo(Official::class, 'official_id', 'id');
    }
}
