<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The timeline on every record (App\Models\Concerns\HasTimeline).
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('system');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
