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
        Schema::create('documents_officials', function (Blueprint $table) {
            $table->id();
            $table->string('nip');
            $table->string('nomor_kontrak');
            $table->timestamps();

            $table->foreign('nip')->references('nip')->on('officials')->onDelete('cascade');
            $table->foreign('nomor_kontrak')->references('nomor_kontrak')->on('documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents_officials');

        Schema::table('documents_officials', function (Blueprint $table) {
            $table->dropForeign(['nip']);
            $table->dropForeign(['nomor_kontrak']);
        });
    }
};
