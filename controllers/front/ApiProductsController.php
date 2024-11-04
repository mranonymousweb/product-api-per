<?php

class ApiProductsController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // بررسی روش درخواست (POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['error' => 'Method not allowed. Please use POST.']);
            die();
        }

        // دریافت ورودی‌ها از POST
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

        // دریافت لیست محصولات از دیتابیس با تعداد محدود برای هر صفحه
        $products = Product::getProducts(
            $this->context->language->id, 
            ($page - 1) * 100, 
            100, 
            'id_product', 
            'ASC'
        );

        // دریافت تعداد کل محصولات برای محاسبه حداکثر صفحات
        $totalProducts = Product::getProducts(
            $this->context->language->id, 
            0, 
            null, 
            'id_product', 
            'ASC'
        );
        $maxPages = ceil(count($totalProducts) / 100);

        // ایجاد خروجی
        $response = [
            'count' => count($products),
            'max_pages' => $maxPages,
            'products' => $products,
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        die();
    }

    private function getProductDetails($page_unique, $page_url)
    {
        $product = null; // برای ذخیره اطلاعات محصول

        // جستجوی محصول با شناسه یا URL
        if (!empty($page_unique)) {
            $product = new Product($page_unique);
        } elseif (!empty($page_url)) {
            $product = $this->getProductByUrl($page_url);
        }

        // ایجاد خروجی
        if ($product && $product->id) {
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
            'image_link' => $this->getProductCoverImage($product),
            'image_links' => $this->getProductImages($product),
            'page_url' => $product->getLink(),
            'short_desc' => $product->description_short,
            'spec' => $this->getProductSpecs($product),
            'registry' => 'registered',
            'guarantee' => $product->guarantee ?? '',
        ];
    }

    private function getProductSpecs($product)
    {
        // ویژگی‌های محصول را دریافت کنید
        return [
            'memory' => $product->memory ?? 'N/A',
            'camera' => $product->camera ?? 'N/A',
            'color' => $product->color ?? 'N/A',
        ];
    }

    private function getProductCoverImage($product)
    {
        $cover = Product::getCover($product->id);
        if ($cover) {
            return $this->context->link->getImageLink($product->link_rewrite, $cover['id_image']);
        }
        return null;
    }

    private function getProductImages($product)
    {
        $images = [];
        $productImages = $product->getImages($this->context->language->id);
        foreach ($productImages as $image) {
            $images[] = $this->context->link->getImageLink($product->link_rewrite, $image['id_image']);
        }
        return $images;
    }

    private function getProductByUrl($page_url)
    {
        // اینجا باید با استفاده از query به جستجوی محصول با لینک `page_url` بپردازید.
        $sql = 'SELECT id_product FROM ' . _DB_PREFIX_ . 'product_lang WHERE link_rewrite = "' . pSQL($page_url) . '"';
        $productId = Db::getInstance()->getValue($sql);

        if ($productId) {
            return new Product($productId);
        }
        return null;
    }
}
