<?php
// Heading
$_['heading_title']                 = 'Enhanced E-Commerce Tracking <span style="font-size:12px; color:#999999"> by <a href="https://vanstudio.co.ua/redirect-more" style="font-size:1em; color:#999999" target="_blank">VanStudio</a></span>';
$_['heading_title_main']            = 'Enhanced E-Commerce Tracking';

// Text
$_['text_success']                  = '<b>Success:</b> You have modified Enhanced E-Commerce Tracking module!';
$_['text_debug_info']               = '<b>Important:</b> All data sent to the Validation Server in <b>debug mode</b> will not show up in Google Analytics reports!';
$_['text_edit']                     = 'Edit Module Settings';
$_['text_home']                     = 'Home';
$_['text_loading']                  = 'Loading...';
$_['text_extensions']               = 'Extensions';
$_['text_extension']                = 'Extension';
$_['text_modules']                  = 'Modules';
$_['text_translator']               = 'Translator';
$_['text_enabled']                  = 'Enabled';
$_['text_disabled']                 = 'Disabled';
$_['text_default']                  = 'Default';
$_['text_confirm']                  = 'Attention: not saved settings will be lost! Сontinue?';
$_['text_multilingual']             = 'Multilingual';
$_['text_multicurrency']            = 'Multicurrency';
$_['text_required_debug']           = 'required in debug mode';
$_['text_configuring_only']         = 'for configuring only';
$_['text_dimension_left']           = 'custom dimensions left';
$_['text_product']                  = 'Product';
$_['text_order']                    = 'Order';

$_['text_js_position_0']            = 'Header - position before the closing &lt;/head&gt; tag';
$_['text_js_position_1']            = 'Header - position before the list of module scripts';
$_['text_js_position_2']            = 'Footer - position after the closing &lt;/footer&gt; tag';
$_['text_js_position_3']            = 'Footer - position before the closing &lt;/body&gt; tag';

$_['text_product_id_0']             = 'Product ID';
$_['text_product_id_1']             = 'Product SKU';
$_['text_product_id_2']             = 'Product Model';

$_['text_product_category_0']       = 'One category of product, for example "Smartphones" (option for best performance)';
$_['text_product_category_1']       = 'One category of product with the highest level of nesting, for example "Personal Computers/Components/Motherboards"';
$_['text_product_category_2']       = 'One category of product with the lowest level of nesting, for example "Personal Computers"';
$_['text_product_category_3']       = 'All categories of product, for example "Computers/Components/Motherboards | Computers/Components/Processors | Phones/Smartphones"';

$_['text_before_changes']           = 'demo code before changes';
$_['text_after_changes']            = 'demo code after changes';
$_['text_video_instruction']        = 'video instruction';
$_['text_gtag_tab']                 = 'Change your the Global Site Tag (gtag.js) tracking code in the Analytics extension (or in the store settings for OpenCart lower 2.1.0.1) as shown in the example below.<br>Replace the string <b>gtag(\'config\', \'UA-XXXXXXXX-YY\');</b> with the string <b>gtag(\'config\', \'UA-XXXXXXXX-YY\', { \'send_page_view\': false });</b> where <b>UA-XXXXXXXX-YY</b> is your Tracking ID<br>and add the following string <b>gtag(\'event\', \'page_view\', { \'event_callback\': function() { ee_start = 1; } });</b>';
$_['text_ga_tab']                   = 'Change your the Google Analytics (analytics.js) tracking code in the Analytics extension (or in the store settings for OpenCart lower 2.1.0.1) as shown in the example below.<br>Replace the string <b>ga(\'send\', \'pageview\');</b> with the string <b>ga(\'send\', \'pageview\', { \'hitCallback\': function() { ee_start = 1; } });</b> <br>*<b>UA-XXXXXXXX-YY</b> is your Tracking ID';
$_['text_gtm_tab']                  = 'In Google Tag Manager, you need to add a custom JavaScript variable that executes a code showed below. The second step is to add this variable (custom JavaScript) to the standard Universal Analytics tag as a field in the more settings section.';

$_['text_your_ip']                  = 'Your IP address';
$_['text_googlebot_ip']             = 'Googlebot IP address range';
$_['text_add_this_value']           = 'Add this value';

$_['text_clear_log']                = 'Logs have been successfully cleared!';

$_['text_author']                   = 'Author';
$_['text_version']                  = 'Version';
$_['text_more_extensions']          = 'More Extensions';
$_['text_support']                  = 'Support';
$_['text_faq']                      = 'FAQ';
$_['text_changelog']                = 'Changelog';
$_['text_opencart_page']            = 'OpenCart Page';
$_['text_demo_site']                = 'Demo Site';
$_['text_demo_admin']               = 'Demo Admin';
$_['text_custom']                   = 'Not recommend updating this extension without serious necessary';

// Button
$_['tab_general']                   = 'General';
$_['tab_advanced']                  = 'Advanced';
$_['tab_impression']                = 'Impression';
$_['tab_click']                     = 'Click';
$_['tab_detail']                    = 'Detail View';
$_['tab_cart']                      = 'Cart';
$_['tab_checkout']                  = 'Checkout';
$_['tab_transaction']               = 'Transaction';
$_['tab_refund']                    = 'Refund';
$_['tab_promotion']                 = 'Internal Promotion';
$_['tab_custom_dimension']          = 'Custom Dimension';
$_['tab_filter']                    = 'Filter';
$_['tab_log']                       = 'Logs';
$_['tab_help']                      = 'Help';

$_['tab_faq']                       = 'FAQ';
$_['tab_changelog']                 = 'Changelog';
$_['tab_support']                   = 'Support';
$_['tab_about']                     = 'About';
$_['tab_offline']                   = 'Offline Info';

// Entry
$_['entry_translator']              = 'Translator';
$_['entry_store']                   = 'Store';
$_['entry_global_status']           = 'Global Status';
$_['entry_multistore']              = 'MultiStore';
$_['entry_tracking_id']             = 'Tracking ID';
$_['entry_order_id']                = 'Order ID';
$_['entry_js_position']             = 'JavaScript Position';

$_['entry_advanced_settings']       = 'Advanced Settings';
$_['entry_language']                = 'Language';
$_['entry_currency']                = 'Currency';
$_['entry_tax']                     = 'Price + Taxes';
$_['entry_total_shipping']          = 'Total + Shipping';
$_['entry_total_tax']               = 'Total + Taxes';
$_['entry_affiliation']             = 'Store Name';
$_['entry_product_id']              = 'Product ID';
$_['entry_product_category']        = 'Product Category';
$_['entry_compatibility']           = 'Compatibility Mode';
$_['entry_ga_callback']             = 'GA Callback';
$_['entry_generate_cid']            = 'Generate Client ID';

$_['entry_index']                   = 'Index';
$_['entry_object']                  = 'Object';
$_['entry_value']                   = 'Value';

$_['entry_status']                  = 'Status';
$_['entry_debug']                   = 'Debug Mode';
$_['entry_log']                     = 'Logs';

$_['entry_checkout_custom']         = 'Custom Checkout Page';

$_['entry_checkout_url']            = 'Checkout Page URL';

$_['entry_order_status']            = 'Order Status';

$_['entry_bot_filter']              = 'Crawlers / Bots';
$_['entry_ip_filter']               = 'IP Address';
$_['entry_admin_tracking']          = 'Administrators';

$_['entry_extended_log']            = 'Extended Logs';

$_['entry_customer_refund']         = 'Customer Refund';

// Button
$_['button_save']                   = 'Save';
$_['button_cancel']                 = 'Cancel';
$_['button_clear']                  = 'Clear';
$_['button_update']                 = 'Update';
$_['button_translator']             = 'Translator';
$_['button_dimension_add']          = 'New Custom Dimension';
$_['button_remove']                 = 'Remove';

// Note
$_['note_tracking_id']              = '<a href="https://support.google.com/analytics/answer/1008080#trackingID" target="_blank">How to get Tracking ID?</a> Important! This extension does not add Google Analytics tracking code to your site. You should add tracking code to the Analytics extension or to the store settings for OpenCart lower 2.1.0.1.';
$_['note_currency']                 = 'Google Analytics has one global currency type (default USD). Businesses that transact in a single currency other than USD can configure a view to use any of the <a href="https://support.google.com/analytics/answer/6205902?hl=ru#supported-currencies" target="_blank">supported currencies</a>. <a href="https://support.google.com/analytics/answer/1010249">Learn how to change the global currency type in your Google Analytics Account</a>.';
$_['note_checkout_custom']          = 'In this mode measuring the checkout process based at one step only. It\'s because all steps display on one page at one time and a failure rate report for each of them will not be accurate. If you have page cache system enabled, you need to clear it after changing this option.';
$_['note_checkout_url']             = 'Set "index.php?route=checkout/checkout" for Default Checkout Page, Journal2 Quick Checkout, AJAX Quick Checkout Module or Best Checkout Module.<br> Set SEO keyword if you use SEO URL for the checkout page.';
$_['note_custom_dimension']         = 'Important! Custom Dimensions may increase your web-server load.';
$_['note_compatibility']            = 'Important! Compatibility Mode may increase your web-server load.';
$_['note_generate_cid']             = 'Note: This option allows getting ecommerce tracking data about users who for some reason have not been tracked by Google Analytics tracking code. Due to the fact that this module can not completely replace Google tracking code, some reports will include \'not set\' value for users whose ids are created by this module.';
$_['note_callback']                 = 'To use the GA callback option, make the following changes to the tracking method that you use (need to do the instructions from one tab only).';

// Help
$_['help_all_status']               = 'Enable/Disable all status options of measuring types';
$_['help_all_debug']                = 'Enable/Disable all debug mode options of measuring types';
$_['help_all_log']                  = 'Enable/Disable all logs options of measuring types';

$_['help_status']                   = 'Enable/Disable all extension features';
$_['help_tracking_id']              = 'Google Analytics <b><i>tracking ID</i></b> is a string like UA-000000-2. It must be included in your tracking code to tell Analytics which account and property to send data to.';
$_['help_multistore']               = 'Enable/Disable individual module settings for each OpenCart store. If this option is enabled, you can send analytics data from different stores to different Google Analytics accounts using different tracking IDs and extension settings. If this option is disabled, the extension settings for default store will be used for all stores.';
$_['help_order_id']                 = 'Set the order number from openсart.com or vanstudio.co.ua. You can find it in your account (orders list) or in the email message received after purchasing this extension.';
$_['help_js_position']              = 'Select the position for adding JavaScript file of this extension on front-end of store. Pay attention to the status icon and tooltip message after this selector.';
$_['help_danger_js_position']       = 'Warning: JavaScript file of this extension is not found on front-end of the store! Please ignore this message if you use javascript compressor (minifier), cache or an extension for page speed optimization and if add to cart actions are displayed in logs. See FAQ (help tab) for more details.';
$_['help_warning_js_position']      = 'Attention: JavaScript file of this extension and default JavaScript file are not found on front-end of the store. Most likely you use javascript compressor (minifier), cache or an extension for page speed optimization and in this case, this message may be ignored. See FAQ (help tab) for more details.';
$_['help_success_js_position']      = 'Success: JavaScript file is included on front-end of the store!';

$_['help_advanced_settings']        = 'Enable/Disable custom advanced settings. If this option is disabled the default advanced settings will be used: <br>Language: Multilingual<br>Currency: Multicurrency<br>Price + Taxes: Enabled<br>Total + Shipping: Enabled<br>Total + Taxes: Enabled<br>Store Name: from Store Settings<br>Product ID: Product ID<br>Product Category: One Category<br>Compatibility Mode: Disabled<br>Generate Client ID: Disabled<br>GA Callback: Disabled';
$_['help_language']                 = 'Select the language of names categories and products to send to Google Analytics';
$_['help_currency']                 = 'Select the currency of price to send to Google Analytics. If selected the multicurrency option and if your store conducts prices in multiple currencies and currency differs from a global Google Analytics currency type (USD by default), Analytics will perform the necessary conversion using the prior day\'s exchange rate. If selected one currency option, all prices in a different currency will be converted by exchange rate specified in the store settings before sending to Google Analytics.';
$_['help_tax']                      = 'Include Taxes to Product Price';
$_['help_total_shipping']           = 'Include Shipping to Total Order Amount';
$_['help_total_tax']                = 'Include Taxes to Total Order Amount';
$_['help_affiliation']              = 'Set the store or affiliation name(e.g. OpenCart Store). If leave this field empty will be used store name option from store settings.';
$_['help_product_id']               = 'Select the product identifier (article, stock keeping unit) to send tо Google Analytics. If selected SKU or Model but it didn\'t set in some product, for this product will use Product ID.';
$_['help_product_category']         = 'Select the view type of product categories to send to Google Analytics';
$_['help_compatibility']            = 'Allows getting more detailed tracking data with custom themes, that include product positions and clicks.';
$_['help_generate_cid']             = 'Generate an anonymous identifier for a particular user, device, or browser instance for which it was not possible to get an identifier generated by Google Analytics tracking code. If <b>GA Callback</b> option is enabled, client ID will be automatically generated after 5-second waiting for a callback.';
$_['help_ga_callback']              = 'Send ecommerce tracking data only when Google Analytics library is loaded and a pageview hit is done sending. If <b>Generate Client ID</b> option is enabled, client ID will be automatically generated after 5-second waiting for a callback.';

$_['help_custom_dimension']         = 'Custom Dimensions for sending additional product data to Google Analytics, the identifiers in curly brackets will be replaced with the corresponding values, for example <i><b>{date_modified}</b></i> to <i><b>2017-07-16 14:53:40</b></i>';

$_['help_debug']                    = 'Send analytics data to the Validation Server and add response to the logs. <b>Important</b>: data sent to the Validation Server will not show up in reports. They are for debugging only.';
$_['help_log']                      = 'Add analytics data to log file. Recommended to use for debugging and checking the module only.';

$_['help_checkout_custom']          = 'Allow measuring the Checkout Process by additional module or theme, e.g. AJAX / Quick / Smart / One Page / Easy / Custom Checkout.';
$_['help_checkout_url']             = 'Current checkout page URL without domain name. It\'s can be default URL \'index.php?route=checkout/checkout\' or SEO keyword like \'checkout\'.';

$_['help_order_status']             = 'Set the order status when an order data is sent to Google Analytics';

$_['help_customer_refund']          = 'Measuring Refunds from Product Returns section on the site (front-end)';

$_['help_bot_filter']               = 'Add which words from user agent string are blocked ecommerce tracking. Use | (vertical bar) as a separator for each value.';
$_['help_ip_filter']                = 'Add which IP addresses are blocked to ecommerce tracking. Use a new line for each value.';
$_['help_admin_tracking']           = 'To block tracking of users who are entered as an administrator. This option does not apply to adding and editing orders in the admin panel and multi-store tracking.';

$_['help_log']                      = 'It is recommended to use logs only within debugging and checking the extension work';
$_['help_extended_log']             = 'Add a full list of tracking parameters to the logs';

// Legend
$_['legend_bulk']                   = 'Bulk Change <p>Changing the same options of all sections by one click</p>';
$_['legend_general']                = 'General Settings';
$_['legend_advanced']               = 'Advanced Settings <p>Changing the advanced settings installed by default</p>';
$_['legend_impression']             = 'Measuring a Product Impression <p>Represents information about a product that has been viewed in a product list</p>';
$_['legend_click']                  = 'Measuring a Product Click <p>Represents information about a click on a product link displayed in a product list</p>';
$_['legend_detail']                 = 'Measuring a Product Details View <p>Represents information about an individual product page that was viewed</p>';
$_['legend_cart']                   = 'Measuring an Addition or Removal from Cart <p>Represents information about the addition, update or removal of a product from a shopping cart</p>';
$_['legend_checkout']               = 'Measuring the Checkout Process <p>Represents information about checkout steps and options in a checkout process</p>';
$_['legend_transaction']            = 'Measuring Transactions <p>Represents information about transaction level details like total revenue, tax, and shipping</p>';
$_['legend_refund']                 = 'Measuring Refunds <p>Represents information about an entire and a partial transaction refund</p>';
$_['legend_promotion']              = 'Measuring Internal Promotions <p>Represents information about impressions and clicks of internal promotions, such as banners displayed to promote a sale on another section of a website</p>';
$_['legend_custom_dimension']       = 'Custom Dimensions <p>Custom dimensions are like default dimensions in your Analytics account, except you create them yourself. You can use them to collect and analyze data that Analytics doesn\'t automatically track. <a href="https://support.google.com/analytics/answer/2709828" target="_blank" rel="nofollow">More info...</a></p>';
$_['legend_filter']                 = 'Filters <p>Settings for blocking spam tracking, web crawlers and administrators</p>';
$_['legend_log']                    = 'Logs <p></p>';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify Enhanced E-Commerce Tracking module!';
$_['error_warning']                 = 'Warning: Please check the form carefully for errors!';
$_['error_mysql_table']             = 'Warning: One or more the extension tables don\'t exist in the database! Please reinstall this extension in <a href="%s">the module list</a> to solve this problem. If the problem doesn\'t solve, see FAQ for more details.';
$_['error_file_exist']              = 'Warning: One or more the extension files don\'t exist in the \'catalog\' folder! Please upload the archive of extension again.';
$_['error_ocmod_modification']      = 'Warning: Please, update the extension modification (Extension / Modifications / Enhanced E-Commerce Tracking) to version %s! The .ocmod.xml modification file for update is located in the .zip archive with module.';
$_['error_vqmod_modification']      = 'Warning: Please, update the .vqmod.xml extension modification file (/vqmod/xml/enhanced-ecommerce-tracking.vqmod.xml) to version %s!';
$_['error_ocmod_with_vqmod']        = 'Warning: Please do not use OCMOD and VQMOD modifications of extension at the same time!';
$_['error_save']                    = 'Warning: The extension was updated, please re-save settings!';
$_['error_tracking_id']             = 'Tracking ID required!';
$_['error_order_id']                = 'Order ID required!';
$_['error_checkout_url']            = 'Checkout Page URL required!';
$_['error_order_status']            = 'You must choose at least one order status!';