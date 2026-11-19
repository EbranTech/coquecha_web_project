<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['shift_id', 'resource_id']);
            $table->index(['resource_id', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_resources');
    }
};
