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
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('user_accounts')->restrictOnDelete();
            $table->string('category', 80);
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['sector_id', 'status', 'created_at']);
            $table->index(['created_by_user_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
