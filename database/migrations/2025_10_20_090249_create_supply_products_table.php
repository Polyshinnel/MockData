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
        Schema::create('supply_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_bill_id')->nullable();
            $table->unsignedBigInteger('supply_box_id');
            $table->unsignedBigInteger('supply_request_id')->nullable();
            $table->integer('plan_quantity');
            $table->decimal('plan_price');
            $table->integer('fact_quantity')->default(0);
            $table->decimal('fact_price')->default(0);
            $table->decimal('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->foreign('supply_bill_id')->references('id')->on('supply_bills');
            $table->foreign('supply_box_id')->references('id')->on('supply_boxes');
            $table->foreign('supply_request_id')->references('id')->on('supply_requests');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_products');
    }
};
