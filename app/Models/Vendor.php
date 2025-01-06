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
    ];

    protected $guarded = [];

    public function document()
    {
        return $this->hasMany(Document::class, 'id_vendor', 'id');
    }
}
