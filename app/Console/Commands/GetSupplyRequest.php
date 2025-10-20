<?php

namespace App\Console\Commands;

use App\Service\RequestSupplyService;
use Illuminate\Console\Command;

class GetSupplyRequest extends Command
{
    private RequestSupplyService $requestSupplyService;
    public function __construct(RequestSupplyService $requestSupplyService)
    {
        $this->requestSupplyService = $requestSupplyService;
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-supply-request';

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
        $supplyRequest = [
            [
                'name' => 'Тестовый товар 1',
                'price' => 1000,
                'quantity' => 2,
                'sku' => '1234567890',
                'brand' => 'Abibas',
                'provider' => 'ABC Provider',
            ],
            [
                'name' => 'Тестовый товар 2',
                'price' => 500,
                'quantity' => 1,
                'sku' => 'asd123',
                'brand' => 'Abibas',
                'provider' => 'ABC Provider',
            ],
            [
                'name' => 'Тестовый товар 3',
                'price' => 200,
                'quantity' => 5,
                'sku' => 'hsr123',
                'brand' => 'Bramox',
                'provider' => 'ABC Provider',
            ],
        ];

//        $supplyRequest = [
//            [
//                'name' => 'Тестовый товар 1',
//                'price' => 1000,
//                'quantity' => 2,
//                'sku' => '1234567890',
//                'brand' => 'Abibas',
//                'provider' => 'ABC Provider',
//            ],
//            [
//                'name' => 'Тестовый товар 4',
//                'price' => 500,
//                'quantity' => 1,
//                'sku' => 'hesa1234',
//                'brand' => 'Abibas',
//                'provider' => 'ABC Provider',
//            ],
//            [
//                'name' => 'Тестовый товар 5',
//                'price' => 200,
//                'quantity' => 5,
//                'sku' => 'test123563',
//                'brand' => 'Bramox',
//                'provider' => 'ABC Provider',
//            ],
//        ];

        $response = $this->requestSupplyService->processingRequest($supplyRequest);
        if(isset($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $this->error($error);
            }
        }
        if(isset($response['info'])) {
            $this->info($response['info']);
        }
    }
}
