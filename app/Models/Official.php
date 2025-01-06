<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    use HasFactory;

    protected $table = 'officials';
    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'nip',
        'nama',
        'jabatan', 
        'periode_jabatan'
    ];

    protected $guarded = [];

    public function documentOfficial()
    {
        return $this->hasMany(DocumentOfficial::class, 'nip', 'nip');
    }
}
