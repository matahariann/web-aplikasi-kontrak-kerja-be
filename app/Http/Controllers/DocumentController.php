<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function index()
    {
    try {
        $documents = Document::with(['vendor', 'contracts'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($documents);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Gagal mengambil data dokumen',
            'message' => $e->getMessage()
        ], 500);
    }
    }

}
