<?php

//auto load only if not autoloaded yet
if (!class_exists('Moosend\TrackerFactory')) {
    require_once realpath(DIR_APPLICATION . '/../') . '/mootracker/vendor/autoload.php';
}

use Moosend\TrackerFactory;

class ControllerExtensionModuleMooTracker extends Controller
{

    /**
     * @var TrackerFactory
     */
    private $trackerFactory;

    public function __construct(Registry $registry, TrackerFactory $trackerFactory = null)
    {
        parent::__construct($registry);

        if ($trackerFactory) {
            $this->trackerFactory = $trackerFactory;
            return;
        }

        $this->trackerFactory = new TrackerFactory();
    }

    private function getFormattedOptions($product)
    {
        $options = $product['option'];
        $formatedOptions = [];

        foreach ($options as $option) {
            $currentFormatedOption = $option['name'];
            $optionValue = $option['value'];

            if (!$optionValue) {
                continue;
            }

            if (isset($currentFormatedOption)) {
                if (is_array($currentFormatedOption)) {
                    array_push($currentFormatedOption, $optionValue);
                    continue;
                }

                $formatedOptions[$currentFormatedOption] = $optionValue;
                continue;
            }

            $formatedOptions[$currentFormatedOption] = $optionValue;
        }

        return $formatedOptions;
    }

    private function locateProductOnCart($productId, $submittedOptions = [])
    {
        if (!!$submittedOptions) {
            return $this->findProductFromCartByOptions($productId, $submittedOptions);
        }

        $foundProducts = $this->findProductsFromCartById($productId);
        return !!count($foundProducts) ? array_shift($foundProducts) : [];
    }

    private function findProductFromCartByOptions($productId, $submitedOptions)
    {
        $products = $this->findProductsFromCartById($productId);
        $formattedOptions = $this->formatSubmittedOptions($submitedOptions);

        foreach ($products as $product) {
            $optionMatches = 0;

            foreach ($product['option'] as $productOption) {
                foreach ($formattedOptions as $submittedOption) {
                    if ($productOption['product_option_id'] == $submittedOption['option_id']) {
                        if ($productOption['product_option_value_id'] == $submittedOption['option_value'] || $productOption['value'] == $submittedOption['option_value']) {
                            $optionMatches++;
                        }
                    }
                }
            }

            if ($optionMatches == count($formattedOptions)) {
                return $product;
            }
        }

        return [];
    }

    private function findProductsFromCartById($productId)
    {
        $products = $this->cart->getProducts();

        return array_filter($products, function ($product) use ($productId) {
            return $product['product_id'] == $productId;
        });
    }

    private function findProductFromCartId($cartId)
    {
        $products = $this->cart->getProducts();

        return array_map(function($product) use ($cartId) {
            if ($product['cart_id'] == $cartId) {
                return $product;
            }
        }, $products);
    }

    private function formatSubmittedOptions($submittedOptions)
    {
        $formatedOptions = [];

        foreach ($submittedOptions as $optionId => $option) {
            if (is_array($option)) {
                foreach ($option as $optionFromArray) {
                    if (!!$optionFromArray) {
                        array_push($formatedOptions, [
                            'option_id' => $optionId,
                            'option_value' => $optionFromArray
                        ]);
                    }
                }
                continue;
            }

            if ($option) {
                array_push($formatedOptions, [
                    'option_id' => $optionId,
                    'option_value' => $option
                ]);
            }
        }

        return $formatedOptions;
    }

    /**
     * Utility function, return the current url
     *
     * @return string
     */
    private function getCurrentUrl()
    {
        if (php_sapi_name() == 'cli') {
            return '';
        }

        $protocol = 'http://';

        if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1))
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
            $protocol = 'https://';
        }

        $url = $protocol . $_SERVER['HTTP_HOST'];

        $url .= $_SERVER['REQUEST_URI'];

        return $url;
    }

    public function page_view()
    {
        //track page views
        if (!$this->config->get('module_mootracker_status')) {
            return;
        }

        $siteId = $this->config->get('module_mootracker_site_id');

        if (empty($siteId)) {
            return;
        }

        $tracker = $this->trackerFactory->create($siteId);
        $tracker->init($siteId);

        // identify
        if ($this->customer->isLogged()) {
            $userName = $this->customer->getFirstName() . " " . $this->customer->getLastName();
            $userEmail = $this->customer->getEmail();

            if (!$tracker->isIdentified($userEmail)) {
                $tracker->identify($userEmail, $userName, [], true)->wait();
            }
        }

        $actual_link = $this->getCurrentUrl();
        if ($this->isProductPage()) {
            $properties = $this->getProduct();
            $productUrl = htmlspecialchars_decode($properties[0]['product']['itemUrl']);
            $tracker->pageView($actual_link, $properties, true)->wait();
        } else {
            $tracker->pageView($actual_link, [], true)->wait();
        }
    }

    public function log_in()
    {
        //track identify
        if (!$this->config->get('module_mootracker_status')) {
            return;
        }
        $this->load->model('account/customer');
        $siteId = $this->config->get('module_mootracker_site_id');

        if (empty($siteId)) {
            return;
        }

        $tracker = $this->trackerFactory->create($siteId);
        $tracker->init($siteId);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $postEmail = $this->request->post['email'];

            $customer = $this->model_account_customer->getCustomerByEmail($postEmail);

            if ($this->customer->isLogged()) {
                $email = $customer['email'];
                $name = $customer['firstname'] . ' ' . $customer['lastname'];

                if (!$tracker->isIdentified($email)) {
                    $tracker->identify($email, $name, [], true)->wait();
                }
            }
        }
    }

    public function cart_updated()
    {
        //track add to cart
        if (!$this->config->get('module_mootracker_status')) {
            return;
        }
        $this->load->model('catalog/product');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/category');
        $this->load->model('tool/image');
        $siteId = $this->config->get('module_mootracker_site_id');

        if (empty($siteId)) {
            return;
        }

        $tracker = $this->trackerFactory->create($siteId);
        $tracker->init($siteId);

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && !$this->isOutputARedirect($this->response->getOutput())) {
            $productId = $this->request->post['product_id'];
            $options = isset($this->request->post['option']) ? $this->request->post['option'] : [];

            // $product = $this->model_catalog_product->getProduct($productId);
            $product = $this->locateProductOnCart($productId, $options);
            $productModel = $this->model_catalog_product->getProduct($productId);

            $productName = $product['name'];
            $productPrice = round($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), 2);

            $productImage = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
            $productUrl = $this->url->link('product/product', 'product_id=' . $productId);
            $productManufacturer = array_key_exists('name', $this->model_catalog_manufacturer->getManufacturer($productModel['manufacturer_id'])) ? $this->model_catalog_manufacturer->getManufacturer($productModel['manufacturer_id'])['name'] : "";

            $props = [];
            $props['itemManufacturer'] = $productManufacturer;
            $props['itemCategory'] = $this->getCategoryNames($productId, $this->model_catalog_product, $this->model_catalog_category);

            if (!!$options) {
                $formattedOptions = $this->getFormattedOptions($product);
                $props = array_merge($formattedOptions, $props);
            }

            //use this for itemTotal
            $itemQuantity = intval($this->request->post['quantity']);
            $total = $productPrice * intval($this->request->post['quantity']);

            $tracker->addToOrder($productId, $productPrice, $productUrl, $itemQuantity, $total, $productName, $productImage, $props, true)->wait();
        }
    }

    public function order_completed()
    {
        //track order completed
        if (!$this->config->get('module_mootracker_status')) {
            return;
        }

        $siteId = $this->config->get('module_mootracker_site_id');

        if (empty($siteId)) {
            return;
        }

        $tracker = $this->trackerFactory->create($siteId);
        $tracker->init($siteId);

        $this->load->model('account/order');

        //Replaced with Account/Order model to work with guest purchases also
        $this->load->model('checkout/order');

        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        $this->load->model('tool/image');

        $orderId = isset($this->session->data['order_id']) && $this->session->data['order_id'] ? $this->session->data['order_id'] : null;

        if ($orderId) {
            $order = $this->model_checkout_order->getOrder($orderId);

            $products = $this->model_checkout_order->getOrderProducts($orderId);
            $trackerOrder = $tracker->createOrder($order['total']);

            foreach ($products as $product) {
                $itemCode = $product['product_id'];
                $itemPrice = $product['price'];
                $itemUrl = $this->url->link('product/product', 'product_id=' . $itemCode);
                $itemName = $product['name'];

                $productModel = $this->model_catalog_product->getProduct($itemCode);
                $itemImage = '';

                if (isset($productModel['image']) && $productModel['image']) {
                    $itemImage = $this->model_tool_image->resize($productModel['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
                }

                $itemQuantity = intval($product['quantity']);
                $itemTotal = floatval($product['total']);
                $itemManufacturer = array_key_exists('name', $this->model_catalog_manufacturer->getManufacturer($productModel['manufacturer_id'])) ? $this->model_catalog_manufacturer->getManufacturer($productModel['manufacturer_id'])['name'] : "";

                $props = [];
                $props['itemManufacturer'] = $itemManufacturer;
                $props['itemCategory'] = $this->getCategoryNames($itemCode, $this->model_catalog_product, $this->model_catalog_category);

                $orderOptions = $this->model_checkout_order->getOrderOptions($orderId, $product['order_product_id']);

                $formatedOrderOptions = $this->formatOrderOptions($orderOptions);

                $props = array_merge($formatedOrderOptions, $props);

                $trackerOrder->addProduct($itemCode, $itemPrice, $itemUrl, $itemQuantity, $itemTotal, $itemName, $itemImage, $props);
            }

            $tracker->orderCompleted($trackerOrder, true)->wait();
        }
    }

    private function formatOrderOptions($orderOptions)
    {
        $formatedOptions = [];

        foreach ($orderOptions as $option) {
            $optionValue = $option['value'];

            if (!$optionValue) {
                continue;
            }

            if (isset($formatedOptions[$option['name']])) {
                if (is_array($formatedOptions[$option['name']])) {
                    array_push($formatedOptions[$option['name']], $optionValue);
                    continue;
                }

                $formatedOptions[$option['name']] = [$formatedOptions[$option['name']]];
                continue;
            }

            $formatedOptions[$option['name']] = $optionValue;
        }

        return $formatedOptions;
    }

    /**
     * @return bool
     */
    private function isProductPage()
    {
        return array_key_exists('product_id', $this->request->get);
    }

    private function isOutputARedirect($output)
    {
        return array_key_exists('redirect', json_decode($output, true));
    }

    /**
     * @return mixed
     */
    private function getCategoryNames($product_id, $productModel, $categoryModel)
    {
        $categories = $productModel->getCategories($product_id);
        $categoryNames = array_map(function ($category) use ($categoryModel) {
            $category_id = $category['category_id'];
            return $categoryModel->getCategory($category_id)['name'];
        }, $categories);
        return implode(", ", $categoryNames) ?: null;
    }

    /**
     * @return array
     */
    private function getProduct()
    {
        $product = $this->model_catalog_product->getProduct($this->request->get['product_id']);
        $categoryModel = $this->model_catalog_product->getCategories($product['product_id']);
        $category = $this->getCategoryNames($this->request->get['product_id'], $this->model_catalog_product, $this->model_catalog_category);
        $productDescription = preg_replace('/(?:<|&lt;)\/?([a-zA-Z]+) *[^<\/]*?(?:>|&gt;)/', '', $product['description']);
        $productPrice = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
        $productImage = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
        $productManufacturer = array_key_exists('name', $this->model_catalog_manufacturer->getManufacturer($product['manufacturer_id'])) ? $this->model_catalog_manufacturer->getManufacturer($product['manufacturer_id'])['name'] : "";
        return [
            [
                'product' => [
                    'itemCode' => (int)$product['product_id'],
                    'itemPrice' => $productPrice,
                    'itemUrl' => $productUrl = $this->url->link('product/product', 'product_id=' . $product['product_id']),
                    'itemQuantity' => intval($product['quantity']),
                    'itemTotal' => $productPrice,
                    'itemImage' => $productImage,
                    'itemName' => $product['name'],
                    'itemDescription' => $productDescription,
                    'itemCategory'  =>  $category,
                    'itemManufacturer'  =>  $productManufacturer
                ]
            ]
        ];
    }
}
