<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upload_and_converts', function (Blueprint $table) {
            $table->id();
            $table->string('request_from_ip');
            $table->string('request_from_website');
            $table->string('user_id');
            $table->string('user_name');
            $table->jsonb('data');
            $table->string('file_name');
            $table->string('link');
            $table->string('file_size');
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_and_converts');
    }
};
