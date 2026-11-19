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
        Schema::create('shift_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('delivered_volume', 12, 2);
            $table->string('volume_unit', 30)->default('litros');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('user_accounts')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_results');
    }
};
