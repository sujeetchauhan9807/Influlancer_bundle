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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('campaign_type_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('budget', 12);
            $table->string('currency')->default('INR');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['pending','active','completed','cancelled'])->default('pending');
            $table->timestamps();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
            $table->foreign('campaign_type_id')->references('id')->on('campaign_types')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
