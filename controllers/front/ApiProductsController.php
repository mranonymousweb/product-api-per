<?php

class ApiProductsController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // حذف اعتبارسنجی کلید API
        // $this->validateApiKey(); // حذف یا غیرفعال کردن این خط

        // دریافت ورودی‌ها
        $page_unique = Tools::getValue('page_unique');
        $page_url = Tools::getValue('page_url');
        $page = (int)Tools::getValue('page', 1); // پیش‌فرض: ۱

        // بررسی ورودی‌ها و واکنش به درخواست
        if (empty($page_unique) && empty($page_url)) {
            // اگر هیچ ورودی‌ای ارسال نشود، لیست محصولات را بازگردانید
            $this->getProducts($page);
        } else {
            // اگر یکی از ورودی‌ها ارسال شده باشد، اطلاعات محصول خاص را بازگردانید
            $this->getProductDetails($page_unique, $page_url);
        }
    }

    private function getProducts($page)
    {
        $products = []; // لیست محصولات

        // اینجا می‌توانید کد مربوط به دریافت لیست محصولات از دیتابیس را اضافه کنید
        $products = Product::getProducts($this->context->language->id, ($page - 1) * 100, 100, 'id_product', 'ASC');

        // ایجاد خروجی
        $response = [
            'count' => count($products),
            'max_pages' => ceil(Product::getProducts($this->context->language->id, 0, null, 'id_product', 'ASC') / 100), // حداکثر صفحات
            'products' => $products,
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        die();
    }

    private function getProductDetails($page_unique, $page_url)
    {
        $product = []; // اینجا می‌توانید کد مربوط به دریافت اطلاعات محصول خاص را اضافه کنید

        // جستجوی محصول با شناسه یا URL
        if (!empty($page_unique)) {
            $product = new Product($page_unique);
        } elseif (!empty($page_url)) {
            // جستجوی محصول با URL
            $product = Product::getProductByLink($page_url); // فرض بر این است که این تابع وجود دارد
        }

        // ایجاد خروجی
        if ($product) {
            $response = [
                'products' => [$this->formatProduct($product)],
            ];
        } else {
            $response = [
                'error' => 'Product not found',
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        die();
    }

    private function formatProduct($product)
    {
        return [
            'title' => $product->name,
            'subtitle' => $product->subtitle ?? '',
            'page_unique' => $product->id,
            'current_price' => $product->price,
            'old_price' => $product->wholesale_price,
            'availability' => $product->available_now ? 'instock' : 'outofstock',
            'category_name' => implode(', ', $product->getCategories()),
            'image_link' => $product->getCover()['url'],
            'image_links' => $product->getImages($this->context->language->id),
            'page_url' => $product->getLink(),
            'short_desc' => $product->description_short,
            'spec' => $this->getProductSpecs($product),
            'registry' => 'registered',
            'guarantee' => $product->guarantee ?? '',
        ];
    }

    private function getProductSpecs($product)
    {
        // اینجا می‌توانید ویژگی‌های محصول را دریافت کنید
        return [
            'memory' => $product->memory ?? 'N/A',
            'camera' => $product->camera ?? 'N/A',
            'color' => $product->color ?? 'N/A',
        ];
    }

    // حذف اعتبارسنجی API Key
    // private function validateApiKey()
    // {
    //     $apiKey = Tools::getValue('api_key');
    //     if ($apiKey !== 'YOUR_SECRET_API_KEY') {
    //         header('HTTP/1.0 403 Forbidden');
    //         echo json_encode(['error' => 'Unauthorized']);
    //         die();
    //     }
    // }
}
