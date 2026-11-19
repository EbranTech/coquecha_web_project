<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_conflict_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->restrictOnDelete();
            $table->foreignId('attempted_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('conflicting_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->dateTime('attempted_starts_at');
            $table->dateTime('attempted_ends_at');
            $table->foreignId('attempted_by_user_id')->nullable()->constrained('user_accounts')->nullOnDelete();
            $table->timestamps();
            $table->index(['resource_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_conflict_logs');
    }
};
