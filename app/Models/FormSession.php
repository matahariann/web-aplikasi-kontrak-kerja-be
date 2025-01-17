<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSession extends Model
{
    use HasFactory;

    protected $table = 'form_sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nip',
        'current_step',
        'temp_data',
        'is_completed'
    ];

    protected $casts = [
        'temp_data' => 'array',
        'is_completed' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nip', 'nip');
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'form_session_id', 'id');
    }

    public function officials()
    {
        return $this->hasMany(Official::class, 'form_session_id', 'id');
    }

    public function document()
    {
        return $this->hasOne(Document::class, 'form_session_id', 'id');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class, 'form_session_id', 'id');
    }
}
