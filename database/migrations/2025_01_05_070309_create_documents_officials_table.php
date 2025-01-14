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
            $table->unsignedBigInteger('official_id'); // Reference to officials.id
            $table->string('nomor_kontrak');
            $table->timestamps();

            $table->foreign('official_id')
                  ->references('id')
                  ->on('officials')
                  ->onDelete('cascade');

            $table->foreign('nomor_kontrak')
                  ->references('nomor_kontrak')
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
        Schema::table('documents_officials', function (Blueprint $table) {
            $table->dropForeign(['official_id']);
            $table->dropForeign(['nomor_kontrak']);
        });

        Schema::dropIfExists('documents_officials');
    }
};
