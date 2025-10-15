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
        Schema::create('influencers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role')->default('influencer');
            $table->string('phone');
            $table->text('bio')->nullable();
            $table->string('profile_image')->nullable();
            $table->Integer('region');
            $table->text('address');
            $table->json('platform_id')->nullable();
            $table->integer('audience_size')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->string('status');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencers');
    }
};
