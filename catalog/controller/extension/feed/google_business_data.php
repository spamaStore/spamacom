<?php
class ControllerExtensionFeedGoogleBusinessData extends Controller
{
    public function index()
    {
        $prefix='feed_google_business_data_';
        if ($this->config->get($prefix.'status')) {
            //load setttings
            $save_to_file = (int)$this->config->get($prefix.'save_to_file');
            $clear_html = (int)$this->config->get($prefix.'clear_html');
            $use_meta = (int)$this->config->get($prefix.'use_meta');
            $google_pid1 = $this->config->get($prefix.'pid1');
            $google_pid2 = $this->config->get($prefix.'pid2');
            $use_tax = (int)$this->config->get($prefix.'use_tax');
            $image_cache = $this->config->get($prefix.'image_cache');
            $disabled_products = (int)$this->config->get($prefix.'disabled_products');
            $sold_out_products = (int)$this->config->get($prefix.'sold_out_products');
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
            $isDefaultLang = true;

            $lang_id="";
            $currency_code="";
            $product_url_parameter="";
            //load url parameters
            if (isset($_GET['tax'])) {//should be disabled in the USA,Canada and India
                $use_tax = (int)$_GET['tax'];
                $file_name_append.="_t".$_GET['tax'];
            }
            if (isset($_GET['lang'])) {
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($_GET['lang']);
                $file_name_append.="_l".$_GET['lang'];
                if ($_GET['lang'] !== $this->config->get('config_language')) {
                    $isDefaultLang=false;
                    $product_url_parameter.="&amp;language=".$_GET['lang'];
                }
            } else {
                if ($language !== $this->config->get('config_language')) {
                    $product_url_parameter.="&amp;language=".$language;
                }
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($language == '' ? $this->config->get('config_language') : $language);
            }
            if (isset($_GET['curr'])) {
                $currency_code=$_GET['curr'];
                $file_name_append.="_c".$_GET['curr'];
                if ($_GET['curr'] !== $this->config->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$_GET['curr'];
                }
            } else {
                if ($currency !== $this->config->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$currency;
                }
                $currency_code = ($currency == '' ? $this->config->get('config_currency') : $currency);//USD
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
            $filetitle='/google_business_data'.$file_name_append.'.csv';
            if ($save_to_file) {
                $dirname = str_replace('catalog/', '', DIR_APPLICATION);
                $filepath = $dirname.$file_location.$filetitle;
                $filepath = str_replace('//', '/', $filepath);
            }

            $image_size = array();
            if ($image_cache !== "direct") {//no cache
                $image_size = explode('x', $image_cache);
            }
            if (count($image_size)!==2) {
                $image_size = array(600,600);//min is 300x300, but FB & MC uses 600x600
            }

            $output = '';
            if (!$save_to_file || ($save_to_file && $start === 0)) {
                if ($save_to_file) {
                    file_put_contents($filepath.'.tmp', "");
                }
                $output = '"ID",ID2,Item title,Final URL,Image URL,Item subtitle,Item description,Item category,Price,Sale price,Contextual keywords,Item address,Tracking template,Custom parameter,Destination URL
';
            }
            $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);

            while (count($products)>0) {
                foreach ($products as $product) {
                    $product_id = $product['product_id'];
                    $model = trim($this->model_extension_feed_google_merchant_center->decodeChars($product['model']));
                    $quantity = $product['quantity'];
                    $status = $product['status'];
                    //skip excluded products
                    if (in_array($product_id, $black_product_id)
                    || (empty($white_product_id) === false && in_array($product_id, $white_product_id) === false)
                    || ($sold_out_products===0 && $quantity<=0)
                    || ($disabled_products===0 && $status==0)
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
                    $g_product_type = array();
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
                            array_unshift($g_product_type, $string);
                        }
                        if ($count>$counter) {
                            $counter = $count;
                            $category_id = $category['category_id'];
                        }
                    }

                    $g_link = str_replace(" ", "%20", $this->url->link('product/product', 'product_id=' . $product_id, $secure));
                    if ($product_url_parameter !== "") {//add currency language parameters
                        $g_link.=(strpos($g_link, "index.php?") !== false ? $product_url_parameter : "?".substr($product_url_parameter, 5));
                    }
                    if (strpos($g_link, 'http') === false) {//add base url if link is only request
                        $g_link = $base_url.$g_link;
                    }
                    $g_link = htmlspecialchars_decode($g_link, ENT_COMPAT);
                    //title & descriptions
                    if ($use_meta && $product['meta_title'] != "") {
                        $g_title = $product['meta_title'];
                    } else {
                        $g_title = $product['name'];
                    }
                    $g_title = $this->model_extension_feed_google_merchant_center->decodeChars($g_title);
                    $g_title = $this->model_extension_feed_google_merchant_center->fixUpperCase($g_title);
                    $g_title = trim($g_title);
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
                    $description = mb_substr($description, 0, 1000, 'UTF-8');
                    //images
                    $g_image_link = '';
                    if ($product['image']) {
                        $g_image_link = $this->model_extension_feed_google_merchant_center->getImageUrl($product['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $base_url, $secure);
                    }
                    //prices
                    $g_sale_price = '';
                    if ((float)$product['special']) {
                        /*$date_start = $product['date_start'];
                        $date_end =$product['date_end'];
                        if ($date_start=='0000-00-00') {
                            $date_start=date("Y-m-d");
                        }
                        if ($date_end!=='0000-00-00' && $date_start < $date_end) {
                            $g_sale_price_effective_date = $date_start.'/'.$date_end;
                        }*/
                        $g_sale_price = $product['special'];
                    }
                    $g_price = ((float)$product['price'] > 0) ? $product['price'] : $this->model_extension_feed_google_merchant_center->getLowestPriceOption($product_id);
                    $g_id1 = ($google_pid1 === 'product_id' ? $product_id : $model);
                    if ($google_pid2 === 'none') {
                        $g_id2 = '';
                    } elseif ($google_pid2 === 'product_id') {
                        $g_id2 = $product_id;
                    } else {
                        $g_id2 = $model;
                    }
                    if ($g_sale_price > 0) {
                        $g_sale_price = $this->currency->format($this->tax->calculate($g_sale_price, $product['tax_class_id'], $use_tax), $currency_code, $currency_value, false).' '.$currency_code;
                    }
                    $g_price = $this->currency->format($this->tax->calculate($g_price, $product['tax_class_id'], $use_tax), $currency_code, $currency_value, false).' '.$currency_code;
                    $g_keywords = trim(str_replace ('; ' , ';' ,str_replace (' ;' , ';' ,str_replace (',' , ';' , str_replace("\n", ";", str_replace("\r", ";", str_replace("\r\n", ";",$product['meta_keyword'] )))))));
                    //fill data
                    $output .= $this->addQuotes($this->fixEncoding($g_id1)).',';//"ID",
                    $output .= $this->addQuotes($this->fixEncoding($g_id2)).',';//ID2,
                    $output .= $this->addQuotes($this->fixEncoding($g_title)).',';//Item title,
                    $output .= $this->addQuotes($this->fixEncoding($g_link)).',';//Final URL,
                    $output .= $this->addQuotes($this->fixEncoding($g_image_link)).',';//Image URL,
                    $output .= ',';//Item subtitle,
                    $output .= $this->addQuotes($this->fixEncoding($description)).',';//Item description,
                    $output .= $this->addQuotes($this->fixEncoding(implode(';',$g_product_type))).',';//Item category,
                    $output .= $this->addQuotes($this->fixEncoding($g_price)).',';//Price,
                    $output .= $this->addQuotes($this->fixEncoding($g_sale_price)).',';//Sale price,
                    $output .= $this->addQuotes($this->fixEncoding($g_keywords)).',';//Contextual keywords,
                    $output .= ',';//Item address,
                    $output .= ',';//Tracking template,
                    $output .= ',';//Custom parameter,
                    $output .= '
';//Destination URL
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
                $this->response->addHeader('Content-Type: text/csv');
				$this->response->addHeader('Content-disposition: attachment;filename=google_business_data'.$file_name_append.'.csv');//google_busines_data.csv
                $this->response->setOutput($output);
            }
        } else {
            $this->response->setOutput('<head><meta name="robots" content="noindex"></head><body>Disabled feed.</body>');
        }
    }

    public static function fixEncoding($string){
		$string=preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $string);
		$string=str_replace("&amp;nbsp;"," ",$string);
		$string=str_replace("&amp;acute;","´",$string);
		$string=str_replace("&amp;rsquo;","’",$string);
		$string=str_replace("&amp;#39;","'",$string);
		$string=str_replace("&amp;reg;","®",$string);
		$string=str_replace("&amp;copy;","©",$string);
		$string=str_replace("&amp;mdash;","—",$string);
		$string=str_replace("&amp;auml;","ä",$string);
		$string=str_replace("&amp;ouml;","ö",$string);
		$string=str_replace("&amp;lsquo;","‘",$string);
		$string=str_replace("&amp;ldquo;","“",$string);
		$string=str_replace("&amp;sbquo;","‚",$string);
		$string=str_replace("&amp;bdquo;","„",$string);
		$string=str_replace("&amp;rdquo;","”",$string);
		$string=str_replace("&amp;ndash;","–",$string);
		$string=str_replace("&amp;permil;","‰",$string);
		$string=str_replace("&amp;euro;","€",$string);
		$string=str_replace("&amp;lsaquo;","‹",$string);
		$string=str_replace("&amp;rsaquo;","›",$string);
		$string=str_replace("&amp;lt;","&lt;",$string);
		$string=str_replace("&amp;gt;","&gt;",$string);
		$string=str_replace("&amp;quot;","&quot;",$string);
		$string=str_replace("&amp;trade;","™",$string);
		$string=str_replace("&amp;amp;","&amp;",$string);
		return $string;
	}

    public static function addQuotes($string)
    {
        if (strlen($string) > 0 && substr($string, 0, 1) != '"') {
            return '"'.str_replace('"','""',$string).'"';
        }
        return $string;
    }
}
