<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('form_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip'); 
            $table->string('current_step')->default('vendor'); 
            $table->json('temp_data')->nullable(); 
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->foreign('nip')
                  ->references('nip')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('form_sessions');
    }
};
