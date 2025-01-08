<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('nama_vendor')->unique();
            $table->string('alamat_vendor');
            $table->string('nama_pj');
            $table->string('jabatan_pj');
            $table->string('npwp')->unique();
            $table->string('bank_vendor');
            $table->string('norek_vendor')->unique();
            $table->string('nama_rek_vendor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendors');
    }
};
