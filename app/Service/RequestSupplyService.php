<?php

namespace App\Service;

use App\Models\Products;
use App\Models\SupplieStatus;
use App\Models\Supply;
use App\Models\SupplyBox;
use App\Models\SupplyProducts;
use App\Models\SupplyRequest;

class RequestSupplyService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function processingRequest(array $requestProducts): array
    {
        $processingInfo = [];
        $productToSupply = [];


        //Проверяем товары на наличие в базе
        foreach ($requestProducts as $requestProduct) {
            $product = $this->productService->getProductData($requestProduct);
            if($product) {
                $productToSupply[] = [
                    'product_id' => $product->id,
                    'quantity' => $requestProduct['quantity'],
                    'price' => $requestProduct['price'],
                    'weight' => $product->weight,
                    'dimensions' => $product->dimensions,
                ];
            } else {
                $processingInfo['errors'][] = "Не удалось распознать продукт: {$requestProduct['name']} с брендом {$requestProduct['brand']} от поставщика {$requestProduct['provider']}";
            }
        }


        //Проверяем что есть хотя бы один продукт
        if(count($productToSupply) < 1) {
            $processingInfo['errors'][] = 'Не удалось распознать ни одного продукта';
            return $processingInfo;
        }


        //Проверяем наличие активной заявки
        $activeSupply = $this->searchActiveSupply();
        if(!$activeSupply) {
            //Если нет активной заявки создаем новую
            $activeSupply = $this->createSupply();
        }

        //Проверяем наличие коробки
        $supplyBox = $this->getSupplyBox($activeSupply);
        if(!$supplyBox) {
            $supplyBox = $this->createSupplyBox($activeSupply);
        }

        //Создаем заявку на поставку
        $supplyRequest = $this->createSupplyRequest($productToSupply, $activeSupply);

        //Добавляем продукты в заявку
        $this->addProductsToSupply($productToSupply, $supplyBox, $supplyRequest);

        $processingInfo[]['info'] = 'Заявка на поставку успешно создана';
        return $processingInfo;
    }

    private function searchActiveSupply(): ?Supply
    {
        $supplyStatus = SupplieStatus::where('active', true)->get();
        $supplyStatusList = [];
        foreach ($supplyStatus as $supplyStatusItem) {
            $supplyStatusList[] = $supplyStatusItem->id;
        }
        return Supply::whereIn('status_id', $supplyStatusList)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    private function getSupplyBox(Supply $supply): ?SupplyBox
    {
        return SupplyBox::where('supply_id', $supply->id)->orderBy('created_at')->first();
    }
    private function createSupply(): Supply
    {
        $createArr = [
            'status_id' => 1,
        ];
        return Supply::create($createArr);
    }

    private function createSupplyBox($supply): SupplyBox
    {
        $createArr = [
            'supply_id' => $supply->id,
        ];
        return SupplyBox::create($createArr);
    }
    private function createSupplyRequest(array $productToSupply, Supply $supply): SupplyRequest
    {
        $product = Products::find($productToSupply[0]['product_id']);
        $providerId = $product->provider_id;
        $createArr = [
            'provider_id' => $product->provider_id,
            'supply_id' => $supply->id,
        ];
        return SupplyRequest::create($createArr);
    }

    private function addProductsToSupply(
        array $productsToSupply,
        SupplyBox $supplyBox,
        SupplyRequest $supplyRequest
    ): void
    {
        foreach ($productsToSupply as $productToSupply) {
            $createArr = [
                'supply_bill_id' => null,
                'supply_box_id' => $supplyBox->id,
                'supply_request_id' => $supplyRequest->id,
                'plan_quantity' => $productToSupply['quantity'],
                'plan_price' => $productToSupply['price'],
                'fact_quantity' => 0,
                'fact_price' => 0,
                'weight' => $productToSupply['weight'],
                'dimensions' => $productToSupply['dimensions'],
                'product_id' => $productToSupply['product_id'],
            ];
            SupplyProducts::create($createArr);
        }

    }
}
