<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id')->unique();
            $table->boolean('is_available')->default(true);
            $table->boolean('block_lunch_break')->default(true);
            $table->time('lunch_break_start_time')->default('12:00:00');
            $table->time('lunch_break_end_time')->default('13:00:00');
            $table->boolean('block_public_holiday')->default(false);
            $table->string('timezone', 100)->default('Asia/Jakarta');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
