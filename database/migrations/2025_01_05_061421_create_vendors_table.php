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
            $table->string('nama_vendor');
            $table->string('alamat_vendor');
            $table->string('nama_pj');
            $table->string('jabatan_pj');
            $table->string('npwp');
            $table->string('bank_vendor');
            $table->string('norek_vendor');
            $table->string('nama_rek_vendor');
            $table->uuid('form_session_id');
            $table->timestamps();

            $table->foreignId('document_id')
                    ->nullable()
                    ->constrained('documents')
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
        Schema::dropIfExists('vendors');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
    }
};
