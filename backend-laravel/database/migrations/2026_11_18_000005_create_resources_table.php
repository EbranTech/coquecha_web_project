<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('resource_type', 80);
            $table->text('description')->nullable();
            $table->decimal('capacity', 12, 2)->nullable();
            $table->string('capacity_unit', 30)->nullable();
            $table->enum('status', ['available', 'maintenance', 'inactive'])->default('available');
            $table->string('location_description', 255)->nullable();
            $table->timestamps();
            $table->index(['status', 'resource_type']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
