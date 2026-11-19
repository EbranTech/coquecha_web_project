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
        Schema::create('service_interruptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('user_accounts')->restrictOnDelete();
            $table->string('cause', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('affected_households')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
            $table->index(['sector_id', 'restored_at', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_interruptions');
    }
};
