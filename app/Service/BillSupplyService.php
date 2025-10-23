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

    public function processingSupplyBill(array $billData): array
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
            $supplyBox = $this->getSupplyBox($billData['supply_id']);
            if(!$supplyProducts) {
                $this->createBillProduct($billProduct, $supplyBill, $supplyBox);
                continue;
            }

            $productsCount = $supplyProducts->count();

            if ($productsCount == 1) {
                $this->processSingleProduct($supplyProducts->first(), $billProduct, $supplyBill, $supplyBox);
            } else {
                $this->processMultipleProducts($supplyProducts, $billProduct, $supplyBill, $supplyBox);
            }
        }
        $billProcessingData['message'][] = 'Счет успешно обработан';
        return $billProcessingData;
    }

    public function deleteSupplyBill($supplyId, $billId): array
    {
        $result = [];
        
        $supplyBill = SupplyBill::where([
            'id' => $billId,
            'supply_id' => $supplyId
        ])->first();
        
        if (!$supplyBill) {
            $result['errors'][] = 'Счет не найден';
            return $result;
        }
        
        $supplyProducts = SupplyProducts::where('supply_bill_id', $billId)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        foreach ($supplyProducts as $product) {
            if ($product->supply_request_id !== null && $product->plan_quantity > 0) {
                $originalProduct = SupplyProducts::where([
                    'supply_request_id' => $product->supply_request_id,
                    'product_id' => $product->product_id,
                    'supply_id' => $supplyId
                ])
                    ->where('id', '!=', $product->id)
                    ->where('plan_quantity', '>', 0)
                    ->first();
                
                if ($originalProduct) {
                    $originalProduct->update([
                        'plan_quantity' => $originalProduct->plan_quantity + $product->plan_quantity
                    ]);
                }
                
                $product->delete();
            } elseif ($product->supply_request_id !== null && $product->plan_quantity == 0) {
                $product->update([
                    'supply_bill_id' => null,
                    'fact_quantity' => 0,
                    'fact_price' => 0,
                ]);
            } elseif ($product->supply_request_id === null && $product->plan_quantity == 0) {
                $product->delete();
            } else {
                $product->update([
                    'supply_bill_id' => null,
                    'fact_quantity' => 0,
                    'fact_price' => 0,
                ]);
            }
        }
        
        $supplyBill->delete();
        
        $result['message'] = 'Счет успешно удален';
        return $result;
    }

    private function getFilteredSupplyProducts($billProduct, $supplyId): ?Collection
    {
        $filter = [
            'product_id' => $billProduct['product_id'],
            'supply_id' => $supplyId
        ];
        $products = SupplyProducts::where($filter)->orderBy('created_at', 'ASC')->get();
        return $products->isEmpty() ? null : $products;
    }

    private function getSupplyBox($supplyId): SupplyBox
    {
        return SupplyBox::where('supply_id', $supplyId)->orderBy('created_at', 'ASC')->first();
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
    private function createBillProduct(
        array $billProduct,
        SupplyBill $supplyBill,
        SupplyBox $supplyBox,
        int $planQuantity = 0,
        float $planPrice = 0,
        int $requestId = null,
        $fact_quantity = null
    ): SupplyProducts
    {
        $createArr = [
            'supply_bill_id' => $supplyBill->id,
            'supply_box_id' => $supplyBox->id,
            'supply_id' => $supplyBox->supply_id,
            'supply_request_id' => $requestId,
            'plan_quantity' => $planQuantity,
            'plan_price' => $planPrice,
            'fact_quantity' => $fact_quantity ?? $billProduct['quantity'],
            'fact_price' => $billProduct['price'],
            'weight' => $billProduct['weight'],
            'dimensions' => $billProduct['dimensions'],
            'product_id' => $billProduct['product_id'],
        ];
        return SupplyProducts::create($createArr);
    }

    private function processSingleProduct(
        SupplyProducts $supplyProduct,
        array $billProduct,
        SupplyBill $supplyBill,
        SupplyBox $supplyBox
    ): void
    {
        if ($supplyProduct->supply_bill_id === null) {
            $supplyProduct->update([
                'supply_bill_id' => $supplyBill->id,
                'fact_quantity' => $billProduct['quantity'],
                'fact_price' => $billProduct['price'],
            ]);
        } else {
            if ($supplyProduct->plan_quantity <= $supplyProduct->fact_quantity) {
                $this->createBillProduct(
                    $billProduct,
                    $supplyBill,
                    $supplyBox,
                    0,
                    0,
                    null
                );
            } else {
                $newPlanQuantity = $supplyProduct->plan_quantity - $supplyProduct->fact_quantity;

                $supplyProduct->update([
                    'plan_quantity' => $supplyProduct->fact_quantity
                ]);

                $this->createBillProduct(
                    $billProduct,
                    $supplyBill,
                    $supplyBox,
                    $newPlanQuantity,
                    $supplyProduct->plan_price,
                    $supplyProduct->supply_request_id
                );
            }
        }
    }

    private function processMultipleProducts(
        Collection $supplyProducts,
        array $billProduct,
        SupplyBill $supplyBill,
        SupplyBox $supplyBox
    ): void
    {
        $billProductTotal = $billProduct['quantity'];

        foreach ($supplyProducts as $supplyProduct) {
            if ($billProductTotal <= 0) {
                break;
            }

            if ($supplyProduct->supply_bill_id === null) {
                if ($billProductTotal <= $supplyProduct->plan_quantity) {
                    $supplyProduct->update([
                        'supply_bill_id' => $supplyBill->id,
                        'fact_quantity' => $billProductTotal,
                        'fact_price' => $billProduct['price'],
                    ]);
                    $billProductTotal = 0;
                } else {
                    $supplyProduct->update([
                        'supply_bill_id' => $supplyBill->id,
                        'fact_quantity' => $supplyProduct->plan_quantity,
                        'fact_price' => $billProduct['price'],
                    ]);
                    $billProductTotal -= $supplyProduct->plan_quantity;
                }
            } elseif ($supplyProduct->plan_quantity > $supplyProduct->fact_quantity) {
                $newPlanQuantity = $supplyProduct->plan_quantity - $supplyProduct->fact_quantity;

                $supplyProduct->update([
                    'plan_quantity' => $supplyProduct->fact_quantity
                ]);

                $factQuantity = min($billProductTotal, $newPlanQuantity);

                $this->createBillProduct(
                    $billProduct,
                    $supplyBill,
                    $supplyBox,
                    $newPlanQuantity,
                    $supplyProduct->plan_price,
                    $supplyProduct->supply_request_id,
                    $factQuantity
                );

                $billProductTotal -= $factQuantity;
            }
        }

        if ($billProductTotal > 0) {
            $this->createBillProduct(
                $billProduct,
                $supplyBill,
                $supplyBox,
                0,
                0,
                null,
                $billProductTotal
            );
        }
    }
}
