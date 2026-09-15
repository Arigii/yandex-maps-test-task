<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parse_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('reviews_fetched')->default(0);
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parse_jobs');
    }
};
