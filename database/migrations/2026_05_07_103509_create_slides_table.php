<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['text3d', 'shape3d', 'image3d', 'model3d', 'mixed'])->default('mixed');
            $table->json('content'); // objets 3D : [{type, position, rotation, scale, color, text, ...}]
            $table->json('camera')->nullable(); // position/target de la caméra pour cette slide
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedInteger('duration')->default(0); // durée en secondes (0 = libre)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};