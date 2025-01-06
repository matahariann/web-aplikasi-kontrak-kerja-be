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
        Schema::create('documents', function (Blueprint $table) {
            $table->string('nomor_kontrak')->primary();
            $table->date('tanggal_kontrak');
            $table->string('paket_pekerjaan');
            $table->year('tahun_anggaran');
            $table->string('nomor_pp');
            $table->date('tanggal_pp');
            $table->string('nomor_hps');
            $table->date('tanggal_hps');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('nomor_pph1');
            $table->date('tanggal_pph1');
            $table->string('nomor_pph2');
            $table->date('tanggal_pph2');
            $table->string('nomor_ukn');
            $table->date('tanggal_ukn');
            $table->date('tanggal_undangan_ukn');
            $table->string('nomor_ba_ekn');
            $table->string('nomor_pppb');
            $table->date('tanggal_pppb');
            $table->string('nomor_lppb');
            $table->date('tanggal_lppb');
            $table->string('nomor_ba_stp');
            $table->string('nomor_ba_pem');
            $table->string('nomor_dipa');
            $table->date('tanggal_dipa');
            $table->string('kode_kegiatan');
            $table->unsignedBigInteger('id_vendor');           
            $table->timestamps();

            $table->foreign('id_vendor')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['id_vendor']);
        });
    }
};
