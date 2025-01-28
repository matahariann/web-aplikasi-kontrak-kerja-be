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
        'document_id',
        'form_session_id',
    ];
    
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id'); 
    }

    public function official()
    {
        return $this->belongsTo(Official::class, 'official_id', 'id');
    }
}
