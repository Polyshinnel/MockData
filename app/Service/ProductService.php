<?php

namespace App\Service;

use App\Models\Brand;
use App\Models\Products;
use App\Models\Provider;

class ProductService
{
    public function getProductData($product): ?Products
    {
        $brand = $this->getBrand($product['brand']);
        if(!$brand) {
            return null;
        }

        $provider = $this->getProvider($product['provider']);
        if(!$provider) {
            return null;
        }
        $filter = [
            'sku' => $product['sku'],
            'brand_id' => $brand->id,
            'provider_id' => $provider->id,
        ];

        $productData = Products::where($filter)->first();
        if(!$productData) {
            $productData = $this->createProduct($product, $brand->id, $provider->id);
        }
        return $productData;
    }

    private function getProvider(string $provider): ?Provider
    {
        return Provider::where('name', $provider)->first();
    }

    private function getBrand(string $brand): ?Brand
    {
        return Brand::where('name', $brand)->first();
    }

    public function getBillProducts($billProduct, $provider): ?Products
    {

        $filter = [
            'sku' => $billProduct['sku'],
            'provider' => $provider,
        ];
        $products = Products::where($filter)->get();

        if($products->isEmpty()) {
            $provider = Provider::find($provider);
            return $this->createProduct(
                [
                    'name' => $billProduct['name'],
                    'sku' => $billProduct['sku']
                ],
                null,
                $provider->id
            );
        }

        if($products->count() > 1) {
            return null;
        }

        return $products->first();
    }

    private function createProduct(array $product, $brandId, $providerId): Products
    {
        $createArr = [
            'name' => $product['name'],
            'sku' => $product['sku'],
            'brand_id' => $brandId,
            'provider_id' => $providerId,
        ];
        return Products::create($createArr);
    }
}
