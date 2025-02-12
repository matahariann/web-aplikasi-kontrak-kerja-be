<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('official_form_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_id')->constrained('officials')->onDelete('cascade');
            $table->uuid('form_session_id');
            $table->foreign('form_session_id')->references('id')->on('form_sessions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['official_id', 'form_session_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('official_form_sessions');
    }
};
