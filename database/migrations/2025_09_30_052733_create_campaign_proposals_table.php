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
        Schema::create('campaign_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('influencer_id');
            $table->decimal('proposed_fee', 10, 2)->nullable();
            $table->enum('status', ['pending', 'negotiation', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('restrict');
            $table->foreign('influencer_id')->references('id')->on('influencers')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_proposals');
    }
};
