<?php

namespace App\Http\Controllers;

use App\Models\Images;

class ImageController extends Controller
{
    public function showImage($id)
    {
        $image = Images::findOrFail($id);
    
        // Baca file gambar dan konversi ke base64
        $path = storage_path('app/public/' . $image->image);
        $imageData = base64_encode(file_get_contents($path));
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $image->id,
                'name' => $image->name,
                'image' => $imageData
            ]
        ]);
    }
}
