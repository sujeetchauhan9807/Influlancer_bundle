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
        Schema::create('campaign_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('influencer_id');
            $table->enum('status', ['pending', 'in progress', 'completed'])->default('pending');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('restrict');
            $table->foreign('influencer_id')->references('id')->on('influencers')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_status');
    }
};
