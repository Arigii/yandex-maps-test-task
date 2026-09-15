<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('source_url', 2048);
            $table->string('yandex_org_id')->nullable()->index();

            $table->string('name')->nullable();
            $table->string('address')->nullable();

            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('ratings_count')->nullable();
            $table->unsignedInteger('reviews_count')->nullable();

            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamp('last_parsed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'yandex_org_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
