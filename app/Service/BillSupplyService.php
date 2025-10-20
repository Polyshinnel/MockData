<?php

namespace App\Service;

use App\Models\Products;
use App\Models\SupplyBill;
use App\Models\SupplyBox;
use App\Models\SupplyProducts;
use Illuminate\Support\Collection;

class BillSupplyService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function processingSupplyBill(array $billData)
    {
        $billProducts = [];
        $billProcessingData = [];
        foreach ($billData['products'] as $product) {
            $productData = $this->productService->getBillProducts($product, $billData['provider_id']);
            if(!$productData) {
                $billProcessingData['errors'][] = "Товар с артикулом {$product['sku']} имеет дубликат";
            } else {
                $billProducts[] = [
                    'product_id' => $productData->id,
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'weight' => $productData->weight,
                    'dimensions' => $productData->dimensions,
                ];
            }
        }

        if(!$billProducts)
        {
            $billProcessingData['errors'][] = 'Нет товаров для обработки';
            return $billProcessingData;
        }

        $supplyBill = $this->createBill($billData);

        foreach ($billProducts as $billProduct) {
            $supplyProducts = $this->getFilteredSupplyProducts($billProduct, $billData['supply_id']);
            if(!$supplyProducts) {
                $supplyBox = $this->getSupplyBox($billData['supply_id']);
                $this->createBillProduct($billProduct, $supplyBill, $supplyBox);
                continue;
            }
            if($supplyProducts->count() > 1) {
                foreach ($supplyProducts as $supplyProduct) {

                }
            }
        }
    }

    private function getFilteredSupplyProducts($billProduct, $supplyId): ?Collection
    {
        $filter = [
            'product_id' => $billProduct['product_id'],
            'supply_id' => $supplyId
        ];
        return Products::where($filter)->get();
    }

    private function getSupplyBox($supplyId): SupplyBox
    {
        return SupplyBox::where('supply_id', $supplyId)->first();
    }

    private function createBill($billData)
    {
        $createArr = [
            'name' => $billData['name'],
            'invoice_number' => $billData['invoice_number'],
            'supply_id' => $billData['supply_id'],
        ];
        return SupplyBill::create($createArr);
    }
    private function createBillProduct(array $billProduct, SupplyBill $supplyBill, SupplyBox $supplyBox): SupplyProducts
    {
        $createArr = [
            'supply_bill_id' => $supplyBill->id,
            'supply_box_id' => $supplyBox->id,
            'supply_request_id' => null,
            'plan_quantity' => 0,
            'plan_price' => 0,
            'fact_quantity' => $billProduct['quantity'],
            'fact_price' => $billProduct['price'],
            'weight' => $billProduct['weight'],
            'dimensions' => $billProduct['dimensions'],
            'product_id' => $billProduct['product_id'],
        ];
        return SupplyProducts::create($createArr);
    }
}
