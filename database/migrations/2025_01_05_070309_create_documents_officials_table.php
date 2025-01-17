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
            $table->unsignedBigInteger('official_id');
            $table->unsignedBigInteger('document_id'); 
            $table->uuid('form_session_id')->nullable();
            $table->timestamps();

            $table->foreign('official_id')
                  ->references('id')
                  ->on('officials')
                  ->onDelete('cascade');

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
        Schema::table('documents_officials', function (Blueprint $table) {
            $table->dropForeign(['official_id']);
            $table->dropForeign(['document_id']);
        });

        Schema::dropIfExists('documents_officials');
    }
};
