<?php
class ControllerExtensionFeedFacebookCatalog extends Controller
{
    public function index()
    {
        $prefix='feed_facebook_catalog_';
        if ($this->config->get($prefix.'status')) {
            //$file_location = $this->config->get($prefix.'file_location');
            //$base_taxonomy = $this->config->get($prefix.'base_taxonomy');
            //$shipping_price = (float)$this->config->get($prefix.'shipping');

            //load setttings
            $save_to_file = (int)$this->config->get($prefix.'save_to_file');
            $size = $this->config->get($prefix.'size_options');
            $color = $this->config->get($prefix.'color_options');
            $pattern = $this->config->get($prefix.'pattern_options');
            $material = $this->config->get($prefix.'material_options');
            $clear_html = (int)$this->config->get($prefix.'clear_html');
            $use_meta = (int)$this->config->get($prefix.'use_meta');
            $google_pid1 = $this->config->get($prefix.'pid1');
            $google_option_ids = $this->config->get($prefix.'option_ids');
            $use_tax = (int)$this->config->get($prefix.'use_tax');
            $image_cache = $this->config->get($prefix.'image_cache');
            $disabled_products = $this->config->get($prefix.'disabled_products');
            $sold_out_products = $this->config->get($prefix.'sold_out_products');
            $language = $this->config->get($prefix.'language');
            $currency = $this->config->get($prefix.'currency');
            $file_location = '';//here set feed file folder, if needed
            //load model
            $this->load->model('catalog/category');
            $this->load->model('catalog/product');
            $this->load->model('extension/feed/google_merchant_center');
            $this->load->model('tool/image');

            $store_id = $this->config->get('config_store_id');
            if (isset($_GET['store'])) {
                $store_id = (int)$_GET['store'];
            }
            $file_name_append = "_s".$store_id;
            //$isDefaultLang = true;

            $lang_id="";
            $currency_code="";
            $product_url_parameter="";
            //load url parameters
            $use_additional_images = true;
            if (isset($_GET['additional_images']) && $_GET['additional_images'] == 0) {
                $use_additional_images = false;
            }
            $use_select_parameter = true;
            if (isset($_GET['select_parameter']) && $_GET['select_parameter'] == 0) {
                $use_select_parameter = false;
            }
            $use_language_parameter = true;
            if (isset($_GET['language_parameter']) && $_GET['language_parameter'] == 0) {
                $use_language_parameter = false;
            }
            $use_currency_parameter = true;
            if (isset($_GET['currency_parameter']) && $_GET['currency_parameter'] == 0) {
                $use_currency_parameter = false;
            }
            if (isset($_GET['tax'])) {//should be disabled in the USA,Canada and India
                $use_tax = (int)$_GET['tax'];
                $file_name_append.="_t".$_GET['tax'];
            }
            $use_tax_rate = 0;
            if (isset($_GET['tax_rate'])) {
                $use_tax_rate = (int)$_GET['tax_rate'];
                $file_name_append.="_tr".$_GET['tax_rate'];
            }
            if (isset($_GET['lang'])) {
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($_GET['lang']);
                $file_name_append.="_l".$_GET['lang'];
                if ($use_language_parameter && $_GET['lang'] !== $this->config->get('config_language')) {
                    //$isDefaultLang=false;
                    $product_url_parameter.="&amp;language=".$_GET['lang'];
                }
            } else {
                if ($use_language_parameter && $language !== $this->config->get('config_language')) {
                    $product_url_parameter.="&amp;language=".$language;
                }
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($language == '' ? $this->config->get('config_language') : $language);
            }
            if (isset($_GET['curr'])) {
                $currency_code=$_GET['curr'];
                $file_name_append.="_c".$_GET['curr'];
                if ($use_currency_parameter && $_GET['curr'] !== $this->config->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$_GET['curr'];
                }
            } else {
                if ($use_currency_parameter && $currency !== $this->config->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$currency;
                }
                $currency_code = ($currency == '' ? $this->config->get('config_currency') : $currency);//USD
            }
            $shipping_price = null;
            if (isset($_GET['shipping_price'])) {//only url parameter, setting removed
                $shipping_price = (float)$_GET['shipping_price'];
                $file_name_append.="_sp".$_GET['shipping_price'];
            }
            $currency_value = $this->currency->getValue($currency_code);
            $black_product_id=array();
            $white_product_id=array();
            if (isset($_GET['include_product_id'])) {
                $white_product_id = explode(",", $_GET['include_product_id']);
                $file_name_append.="_ip".implode('-', $white_product_id);
            } elseif (isset($_GET['exclude_product_id'])) {
                $black_product_id = explode(",", $_GET['exclude_product_id']);
                $file_name_append.="_ep".implode('-', $black_product_id);
            }
            $black_category_id=array();
            $white_category_id=array();
            if (isset($_GET['include_category_id'])) {
                $white_category_id = explode(",", $_GET['include_category_id']);
                $file_name_append.="_ic".implode('-', $white_category_id);
            } elseif (isset($_GET['exclude_category_id'])) {
                $black_category_id = explode(",", $_GET['exclude_category_id']);
                $file_name_append.="_ec".implode('-', $black_category_id);
            }
            $base_url="";
            if (isset($this->ssl)) {
                $base_url = $this->config->get('config_ssl');
                $secure = true;
            } else {
                $base_url = $this->config->get('config_url');
                $secure = false;
            }
            if ($base_url === "") {
                $domainName = $_SERVER['HTTP_HOST'].'/';
                if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
                    $base_url = "https://".$domainName;
                    $secure = true;
                } else {
                    $base_url = "http://".$domainName;
                    $secure = false;
                }
            }

            $start = 0;
            $limit = 1000;
            $redirect = 10;//set 1 to disable
            $product_count = 0;//onle used if $save_to_file = 1
            if ($save_to_file) {
                if (isset($_GET['redirect'])) {
                    $redirect = (int)$_GET['redirect'];
                    if ($redirect < 1) {
                        $redirect = 10;
                    }
                }
                $product_count = $this->model_extension_feed_google_merchant_center->getProductCount($store_id);
                $step = ceil(($product_count/$redirect)/10)*10;
                if ($limit < $step) {
                    $limit = $step;
                }
            }
            if (isset($_GET['start'])) {
                $start = (int)$_GET['start'];
            }
            if (isset($_GET['limit'])) {
                $limit = (int)$_GET['limit'];
                if ($limit < 1) {
                    $limit = 1000;
                }
            }
            if ($save_to_file) {
                $filetitle='/facebook_catalog'.$file_name_append.'.xml';
                $dirname = str_replace('catalog/', '', DIR_APPLICATION);
                $filepath = $dirname.$file_location.$filetitle;
                $filepath = str_replace('//', '/', $filepath);
            }

            $image_size = array();
            if ($image_cache !== "direct") {//no cache
                $image_size = explode('x', $image_cache);
            }
            if (count($image_size)!==2) {
                $image_size = array(600,600);
            }

            $f_shipping="";
            if ($shipping_price != null) {//shipping flat rate, use merchant account instead
                $shippingFlat = $this->currency->format($shipping_price, $currency_code, $currency_value, false);
                $f_shipping.="<shipping><price>".$shipping_price. ' '.$currency_code."</price></shipping>";
            }

            $size_options = array();
            $size_attributes = array();
            if (is_array($size)) {
                foreach ($size as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $size_options[]=substr($value, 1);
                    } else {
                        $size_attributes[]=substr($value, 1);
                    }
                }
            }

            $color_options = array();
            $color_attributes = array();
            if (is_array($color)) {
                foreach ($color as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $color_options[]=substr($value, 1);
                    } else {
                        $color_attributes[]=substr($value, 1);
                    }
                }
            }

            $material_options = array();
            $material_attributes = array();
            if (is_array($material)) {
                foreach ($material as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $material_options[]=substr($value, 1);
                    } else {
                        $material_attributes[]=substr($value, 1);
                    }
                }
            }

            $pattern_options = array();
            $pattern_attributes = array();
            if (is_array($pattern)) {
                foreach ($pattern as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $pattern_options[]=substr($value, 1);
                    } else {
                        $pattern_attributes[]=substr($value, 1);
                    }
                }
            }

            $output = '';
            if (!$save_to_file || ($save_to_file && $start === 0)) {
                if ($save_to_file) {
                    file_put_contents($filepath.'.tmp', "");
                }
                $output  = '<?xml version="1.0" encoding="UTF-8" ?>';
                $output .= '<rss version="2.0">';
                $output .= '<channel>';
                $output .= '<link>'.$base_url.'</link>';
                $output .= '<title>'.$this->config->get('config_name').'</title>';
            }
            $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);

            while (count($products)>0) {
                foreach ($products as $product) {
                    $product_id = $product['product_id'];
                    $model = trim($this->model_extension_feed_google_merchant_center->decodeChars($product['model']));
                    $base_quantity = $product['quantity'];
                    $status = $product['status'];
                    //skip excluded products
                    if (in_array($product_id, $black_product_id)
                    || (empty($white_product_id) === false && in_array($product_id, $white_product_id) === false)
                    || ($sold_out_products==="skip products" && $base_quantity<=0)
                    || ($disabled_products==="skip products" && $status==0)
                    ) {
                        continue;
                    }
                    //skip excluded categories
                    $categories = $this->model_catalog_product->getCategories($product_id);
                    if (empty($white_category_id) === false || empty($black_category_id) === false) {
                        $category_continue = false;
                        $is_white_category = 2;
                        foreach ($categories as $category) {
                            if (in_array($category['category_id'], $black_category_id)) {
                                $category_continue = true;
                            }
                            if (empty($white_category_id) === false && $is_white_category != 1) {
                                if (in_array($category['category_id'], $white_category_id) === false) {
                                    $is_white_category = 0;
                                } else {
                                    $is_white_category = 1;
                                }
                            }
                        }
                        if ($category_continue || $is_white_category == 0) {
                            continue;
                        }
                    }
                    $f_product_type = array();
                    $category_id='';
                    $counter=0;
                    foreach ($categories as $category) {
                        $path = $this->model_extension_feed_google_merchant_center->getPath($category['category_id'], $lang_id, $store_id);
                        $count=1;
                        if ($path) {
                            $string = '';
                            foreach (explode('_', $path) as $path_id) {
                                $category_info = $this->model_extension_feed_google_merchant_center->getCategory($path_id, $lang_id, $store_id);
                                $count++;
                                if ($category_info) {
                                    if (!$string) {
                                        $string = trim(htmlspecialchars_decode($category_info['name'], ENT_COMPAT));
                                    } else {
                                        $string .= ' > ' . trim(htmlspecialchars_decode($category_info['name'], ENT_COMPAT));
                                    }
                                }
                            }
                            $string = str_replace(", ", " ", $string);
                            $string = str_replace(",", " ", $string);
                            array_unshift($f_product_type, $string);
                        }
                        if ($count>$counter) {
                            $counter = $count;
                            $category_id = $category['category_id'];
                        }
                    }
                    $f_google_product_category = '';
                    $category_id_google = $this->model_extension_feed_google_merchant_center->getTaxonomy($category_id);
                    $is_apparel = false;
                    if (isset($category_id_google['taxonomy_id']) && isset($category_id_google['name'])) {
                        $f_google_product_category_id = $category_id_google['taxonomy_id'];
                        $f_google_product_category = $category_id_google['name'];
                        $is_apparel = $this->model_extension_feed_google_merchant_center->isApparel($f_google_product_category_id);
                    } else {
                        $base_taxonomy = $this->config->get($prefix.'base_taxonomy');
                        if (!is_array($base_taxonomy)) {
                            if (substr($base_taxonomy, 0, 1) === '[') {
                                $base_taxonomy = json_decode($base_taxonomy);
                            } else {
                                $base_taxonomy = array('');
                            }
                        }
                        $f_google_product_category_id = reset($base_taxonomy);
                        if ((int)$f_google_product_category_id) {
                            $is_apparel = $this->model_extension_feed_google_merchant_center->isApparel($f_google_product_category_id);
                        }
                    }

                    $link = str_replace(" ", "%20", $this->url->link('product/product', 'product_id=' . $product_id, $secure));
                    if ($product_url_parameter !== "") {//add currency language parameters
                        $link.=(strpos($link, "index.php?") !== false ? $product_url_parameter : "?".substr($product_url_parameter, 5));
                    }
                    if (strpos($link, 'http') === false) {//add base url if link is only request
                        $link = $base_url.$link;
                    }
                    //title & descriptions
                    if ($use_meta && $product['meta_title'] != "") {
                        $f_title = $product['meta_title'];
                    } else {
                        $f_title = $product['name'];
                    }
                    $f_title = $this->model_extension_feed_google_merchant_center->decodeChars($f_title);
                    $f_title = $this->model_extension_feed_google_merchant_center->fixUpperCase($f_title);
                    $f_title = trim($f_title);
                    if ($use_meta && $product['meta_description'] != "") {
                        $description = $product['meta_description'];
                        if (strlen($description) <= 5) {
                            $description = $product['description'];
                        }
                    } else {
                        $description=$product['description'];
                        $desc_len = strlen($description);
                        if ($desc_len <= 5 && $product['meta_description']!="" && $desc_len < strlen($product['meta_description'])) {
                            $description = $product['meta_description'];
                        }
                    }
                    $description = $this->model_extension_feed_google_merchant_center->decodeChars($description);
                    if ($clear_html) {
                        $description= str_replace("
                        ", " ", str_replace("\n", " ", str_replace("\t", " ", str_replace("\r", " ", str_replace("\r\n", " ", $this->model_extension_feed_google_merchant_center->strip_html_tags($description))))));
                        while (strpos($description, "  ") !== false) {
                            $description=str_replace("  ", " ", $description);
                        }
                        $description=trim($description);
                        while ($this->model_extension_feed_google_merchant_center->startsWith($description, " ") || $this->model_extension_feed_google_merchant_center->endsWith($description, " ")) {
                            $description = $this->model_extension_feed_google_merchant_center->clearDescription($description, " ");
                        }
                    }
                    $description = mb_substr($description, 0, 5000, 'UTF-8');
                    $f_brand = trim($this->model_extension_feed_google_merchant_center->decodeChars($product['manufacturer']));
                    $f_condition = 'new';//possible add a feature
                    //images
                    $f_image_link = '';
                    $f_additional_image_link = array();
                    if ($product['image']) {
                        $f_image_link = $this->model_extension_feed_google_merchant_center->getImageUrl($product['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $base_url, $secure);
                        if ($use_additional_images) {
                            $additional_images = $this->model_extension_feed_google_merchant_center->getImages($product_id, $product['image']);
                            $additional_images_counter = 0;
                            $max_image_count = 10;
                            foreach ($additional_images as $value) {
                                if ($max_image_count == 0) {
                                    break;
                                }
                                $max_image_count--;
                                $additional_images_url = $this->model_extension_feed_google_merchant_center->getImageUrl($value['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $base_url, $secure);
                                $additional_images_counter += strlen($additional_images_url)+1;
                                if ($additional_images_counter <= 2000) {
                                    $f_additional_image_link[] = $additional_images_url;
                                }
                            }
                        }
                    }
                    //gtin & mpn
                    $f_mpn = $product['mpn'];
                    if ($f_mpn === '') {//if empty use model instead
                        $f_mpn = $model;
                    }
                    $f_gtin=$product['upc'];//upc will be used if set in options
                    if ($f_gtin==='') {
                        $f_gtin=$product['ean'];
                    }
                    if ($f_gtin==='') {
                        $f_gtin=$product['jan'];
                    }
                    if ($f_gtin==='') {
                        $f_gtin=$product['isbn'];
                    }
                    //prices
                    $f_sale_price_effective_date = '';
                    $base_sale_price = '';
                    if ((float)$product['special']) {
                        $date_start = $product['date_start'];
                        $date_end =$product['date_end'];
                        if ($date_start=='0000-00-00') {
                            $date_start=date("Y-m-d");
                        }
                        if ($date_end!=='0000-00-00' && $date_start < $date_end) {
                            $f_sale_price_effective_date = $date_start.'/'.$date_end;
                        }
                        $base_sale_price = $product['special'];
                    }
                    $base_price = ((float)$product['price'] > 0) ? $product['price'] : $this->model_extension_feed_google_merchant_center->getLowestPriceOption($product_id);
                    //product tab color, NO gender & NO age group
                    $product_tab_data = $this->model_extension_feed_google_merchant_center->getProductTabData($product_id, $lang_id);
                    $color = trim($product_tab_data['color']);
                    //attributes
                    $size_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $size_attributes, $lang_id);
                    $color_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $color_attributes, $lang_id);
                    $material_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $material_attributes, $lang_id);
                    $pattern_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $pattern_attributes, $lang_id);
                    //options
                    $options = $this->model_extension_feed_google_merchant_center->getOptions($product_id, array_merge($size_options, $color_options, $material_options, $pattern_options), $lang_id);
                    $f_item_group_id = $product_id;
                    if ($google_pid1 === 'model') {
                        $f_item_group_id = $model;
                    }
                    $weight = $product['weight'];
                    foreach ($options as $key => $option_group) {
                        $f_id = array();
                        if ($google_pid1 === 'product_id') {
                            $f_id[] = $product_id;
                        } else {
                            $f_id[] = $model;
                        }
                        $f_shipping_weight = $weight;
                        $f_price = $base_price;
                        $f_sale_price = $base_sale_price;
                        $option_price = 0;
                        $option_shipping_weight = 0;
                        $option_quantity = 0;//only not subtracting items
                        $option_subtract = 0;//will use 1 if any subtract is 1
                        $option_names = array();
                        $option_select = array();
                        $option_checkbox = array();
                        ksort($option_group);//order to option_value_id
                        foreach ($option_group as $option_value_id => $option) {
                            $option_shipping_weight += ($option['weight_prefix'] === '+' ? $option['weight'] : -$option['weight']);
                            if ((int)$option['subtract'] && ($option_subtract == 0 || $option_quantity > $option['quantity'])) {
                                $option_quantity = $option['quantity'];//use only lowest quantity if substract is 1, else option quantity is inaccurate
                                $option_subtract = 1;
                            }
                            $f_gtin = ((!isset($option['upc']) || $option['upc']!='') ? $f_gtin : $option['upc']);//will propably not work, no setting in oc admin
                            $option_price = $option_price+($option['price_prefix'] === '+' ? $option['price'] : -$option['price']);
                            if ($option['name']!='' && $option_value_id!='') {
                                $option_names[$option['option_id']]=$option['name'];
                                if ($google_option_ids === 'option_id') {//id option append
                                    $f_id[]=$option_value_id;
                                } else {
                                    $f_id[]=$this->model_extension_feed_google_merchant_center->decodeChars($option['name']);
                                }
                                if ($option['type']=='select') {//url selection parameters
                                    $option_select[]=$option['product_option_value_id'];
                                } else {
                                    $option_checkbox[]=$option['product_option_value_id'];
                                }
                            }
                        }
                        $f_price += $option_price;
                        if ($f_sale_price > 0) {
                            $f_sale_price += $option_price;
                            $f_sale_price = $this->currency->format($this->tax->calculate($f_sale_price, $product['tax_class_id'], $use_tax), $currency_code, $currency_value, false).' '.$currency_code;
                        }
                        $f_price = $this->currency->format($this->tax->calculate($f_price, $product['tax_class_id'], $use_tax), $currency_code, $currency_value, false).' '.$currency_code;

                        $f_shipping_weight = $this->weight->format(($option_shipping_weight+$f_shipping_weight), $product['weight_class_id'], '.', '');
                        if (strpos($f_shipping_weight, 'g') === false && strpos($f_shipping_weight, 'lb') === false && strpos($f_shipping_weight, 'oz') === false) {
                            $f_shipping_weight = '0.00kg';
                        }

                        if ($sold_out_products==="skip products" && $option_quantity<=0 && $option_subtract == 1) {//skip product with 0 quantity and enabled subtract
                            continue;
                        }

                        $f_availability = 'in stock';//default
                        $quantity = $base_quantity;//not really used, but set it to get the vaule in the feed if required
                        if ($status == 0) {//disabled
                            $f_availability = $disabled_products;
                        } elseif ($quantity == 0) {//if main quantity is 0, you can't order, so always sold out
                            $f_availability = $sold_out_products;
                        } elseif ($option_subtract == 1) {//option quantity might be inacurate, but use it
                            if ($option_quantity == 0) {
                                $f_availability = $sold_out_products;
                            } else {
                                $quantity = $option_quantity;
                            }
                        }
                        $product_tab_data = $this->model_extension_feed_google_merchant_center->getProductTabData($product_id, $lang_id);
                        $f_age_group = ($is_apparel ? $product_tab_data['age_group'] : '');
                        $f_gender = ($is_apparel ? $product_tab_data['gender'] : '');
                        $f_color = array();
                        foreach ($color_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $f_color[] = $option_names[$value];
                            }
                        }
                        $f_color = array_merge($f_color, $color_attribute);
                        if ($color !== '') {//empty($f_color) &&
                            $f_color[]=$color;
                        }
                        $f_size = array();
                        foreach ($size_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $f_size[] = $option_names[$value];
                            }
                        }
                        $f_size = array_merge($f_size, $size_attribute);
                        $f_material = array();
                        foreach ($material_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $f_material[] = $option_names[$value];
                            }
                        }
                        $f_material = array_merge($f_material, $material_attribute);
                        $f_pattern = array();
                        foreach ($pattern_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $f_pattern[] = $option_names[$value];
                            }
                        }
                        $f_pattern = array_merge($f_pattern, $pattern_attribute);

                        $f_link = $link;
                        if ($use_select_parameter) {
                            if (!empty($option_select)) {
                                $f_link.=(strpos($f_link, "?") !== false ? '&amp;select='.implode(',',$option_select) : '?select='.implode(',',$option_select));
                            }
                            if (!empty($option_checkbox)) {
                                $f_link.=(strpos($f_link, "?") !== false ? '&amp;checkbox='.implode(',',$option_checkbox) : '?checkbox='.implode(',',$option_checkbox));
                            }
                        }
                        //fill data
                        $output .= '
<item>';
                        $output .= '<id><![CDATA['.implode('-',$f_id).']]></id>';
                        $output .= '<item_group_id><![CDATA['.$f_item_group_id.']]></item_group_id>';
                        $output .= '<link>'.$f_link.'</link>';
                        $output .= '<title><![CDATA['.$f_title.']]></title>';
                        $output .= '<description><![CDATA['.$description.']]></description>';
                        $output .= '<brand><![CDATA['.$f_brand.']]></brand>';
                        $output .= '<gtin>'.$f_gtin.'</gtin>';
                        $output .= '<mpn><![CDATA['.$f_mpn.']]></mpn>';
                        $output .= '<image_link>'.$f_image_link.'</image_link>';
                        $output .= '<additional_image_link>'.implode(',',$f_additional_image_link).'</additional_image_link>';
                        $output .= '<price>'.$f_price.'</price>';
                        $output .= '<sale_price>'.$f_sale_price.'</sale_price>';
                        $output .= '<sale_price_effective_date>'.$f_sale_price_effective_date.'</sale_price_effective_date>';
                        $output .= '<product_type><![CDATA['.implode(',',$f_product_type).']]></product_type>';
                        $output .= '<google_product_category><![CDATA['.$f_google_product_category.']]></google_product_category>';
                        $output .= '<availability>'.$f_availability.'</availability>';
                        $output .= $f_shipping;
                        $output .= '<shipping_weight>'.$f_shipping_weight.'</shipping_weight>';
                        $output .= '<condition>'.$f_condition.'</condition>';
                        $output .= ($f_age_group === '') ? '' : '<age_group>'.$f_age_group.'</age_group>';
                        $output .= ($f_gender === '') ? '' : '<gender>'.$f_gender.'</gender>';
                        $output .= '<size><![CDATA['.implode(',', $f_size).']]></size>';
                        $output .= '<color><![CDATA['.implode(',', $f_color).']]></color>';
                        $output .= '<pattern><![CDATA['.implode(',', $f_pattern).']]></pattern>';
                        $output .= '<material><![CDATA['.implode(',', $f_material).']]></material>';
                        $output .= '</item>';
                    }
                }

                $start = $start + $limit;
                if (!$save_to_file) {
                    $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);
                } else {
                    $products = array();
                    file_put_contents($filepath.'.tmp', $output, FILE_APPEND | LOCK_EX);
                    $output = "";
                }

            }
            if (!$save_to_file || ($save_to_file && $product_count <= $start)) {
                $output .= '</channel>';
                $output .= '</rss>';
            }
            if ($save_to_file) {
                file_put_contents($filepath.'.tmp', $output, FILE_APPEND | LOCK_EX);
                if ($product_count <= $start) {//finish processing
                    rename($filepath.'.tmp', $filepath);
                    $file_url = $file_location . $filetitle;
                    if (substr($file_url, 0, 1) === "/" && substr($base_url, -1) === "/") {
                        $file_url = substr($file_url, 1);
                    }
                    $file_url = $base_url.$file_url;
                    header('Location: ' . $file_url, true, 302);

                } else {//redirect back to processing next batch
                    $link = $_SERVER['REQUEST_URI'];
                    $link = $this->model_extension_feed_google_merchant_center->setParameterURL($link, 'start', $start);
                    $link = $this->model_extension_feed_google_merchant_center->setParameterURL($link, 'limit', $limit);
                    $link = ($secure ? "https" : "http")."://".$_SERVER['HTTP_HOST'].$link;
                    header('Location: '.$link, true, 302);
                }
                die();
            } else {
                header('Content-Type: text/xml; charset=UTF-8');
                $this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
                print($output);
                exit(0);
            }
        } else {
            $this->response->setOutput('<head><meta name="robots" content="noindex"></head><body>Disabled feed.</body>');
        }
    }
}
