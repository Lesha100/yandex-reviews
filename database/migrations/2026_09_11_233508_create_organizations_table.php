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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('yandex_url');
            $table->string('yandex_id')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('ratings_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('last_parsed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'yandex_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
