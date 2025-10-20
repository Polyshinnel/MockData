<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddSupplyBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-supply-bill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $billData = [
            'provider_id' => 1,
            'supply_id' => 1,
            'name' => 'Счет на оплату № 655225 от 14 октября 2025 г.',
            'invoice_number' => '655225',
            'products' => [
                [
                    'name' => 'Тестовый товар 1',
                    'sku' => '1234567890',
                    'quantity' => 1,
                    'price' => 1300
                ],
                [
                    'name' => 'Тестовый товар 2',
                    'sku' => 'asd123',
                    'quantity' => 1,
                    'price' => 600
                ],
                [
                    'name' => 'Тестовый товар 7',
                    'sku' => 'asada123',
                    'quantity' => 1,
                    'price' => 1000
                ]
            ]
        ];

    }
}
