<?php

namespace Database\Seeders;

use App\Models\Images;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File as FileFacade; 

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Pastikan direktori storage/app/public/images sudah dibuat
        $targetDir = storage_path('app/public/images');
        if (!FileFacade::exists($targetDir)) {
            FileFacade::makeDirectory($targetDir, 0777, true);
        }

        // Copy file gambar dari source ke storage
        $sourceImage = public_path('images/logo_komdigi.png');
        $targetImage = 'images/logo_komdigi.png';
        
        if (FileFacade::exists($sourceImage)) {
            FileFacade::copy($sourceImage, storage_path('app/public/' . $targetImage));
        }

        // Buat record di database
        Images::create([
            'name' => 'Logo Komdigi',
            'image' => $targetImage,
        ]);
    }
}
