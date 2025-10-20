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
        Schema::create('supply_bills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('invoice_number');
            $table->unsignedBigInteger('supply_id');
            $table->timestamps();

            $table->foreign('supply_id')->references('id')->on('supplies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_bills');
    }
};
