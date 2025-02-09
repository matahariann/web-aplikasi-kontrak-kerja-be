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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_kontrak');
            $table->string('deskripsi');
            $table->integer('jumlah_orang');
            $table->integer('durasi_kontrak');
            $table->integer('nilai_perkiraan_sendiri');
            $table->integer('nilai_kontral_awal');
            $table->integer('nilai_kontrak_akhir');
            $table->unsignedBigInteger('document_id'); 
            $table->uuid('form_session_id')->nullable();
            $table->timestamps();

            $table->foreign('document_id') 
                  ->references('id')
                  ->on('documents')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['nomor_kontrak']);
        });
    }
};
