<?php

namespace App\Service;

use App\Models\Products;

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

        foreach ($billProducts as $billProduct) {

        }
    }

    private function getFilteredSupplyProducts($billProduct, $supplyId)
    {
        $filter = [
            'product_id' => $billProduct['product_id'],
            'supply_id' => $supplyId
        ];
        return Products::where($filter)->get();
    }
}
