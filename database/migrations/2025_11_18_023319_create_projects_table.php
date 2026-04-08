<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('made_at')->nullable();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('gallery')->nullable();
            $table->json('technologies')->nullable();
            $table->json('features')->nullable();
            $table->json('results')->nullable();
            $table->enum('status', ['Ongoing', 'Completed'])->default('Completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
