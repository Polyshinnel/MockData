<?php

use App\Models\SupplieStatus;
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
        Schema::create('supplie_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active');
            $table->timestamps();
        });

        $dataItems = [
            [
                'name' => 'Запланирована',
                'active' => true
            ],
            [
                'name' => 'Проверена',
                'active' => true
            ],
            [
                'name' => 'Принята',
                'active' => false
            ],
            [
                'name' => 'Удалена',
                'active' => false
            ]
        ];
        foreach ($dataItems as $dataItem) {
            SupplieStatus::create($dataItem);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplie_statuses');
    }
};
