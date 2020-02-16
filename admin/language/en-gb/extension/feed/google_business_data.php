<?php
// Heading
$_['heading_title']             = 'Google Adwords Business Data (remarketing via analytics)';
$_['text_edit']                 = 'Edit Feed Settings';

// Text
$_['text_feed']                 = 'Feeds';
$_['text_success']              = 'Success: You have modified Google Adwords Business Data feed!';

//options
$_['text_product_id']           = 'Product ID';
$_['text_model']                = 'Model';
$_['text_direct_image']         = 'Use images directly (without cache)';
$_['text_minimal_image']        = ' (minimal image size)';
$_['text_attribute']            = ' (attribute)';
$_['text_option']               = ' (option)';
$_['text_none']                 = 'Do not use';

// Entry
$_['entry_status']              = 'Status:';
$_['entry_save_to_file']        = 'Save to file:';
$_['entry_feed_url']            = 'Feed URL:';
$_['entry_use_meta']            = 'Use meta title/description:';
$_['entry_clear_html']          = 'Remove HTML tags from the description:';
$_['entry_pid1']                = 'Main Product ID:';
$_['entry_pid2']                = 'Second Product ID:';
$_['entry_use_tax']             = 'Include taxes in the price:';
$_['entry_image_cache']         = 'Image format:';
$_['entry_disabled_products']   = 'Include disabled products:';
$_['entry_sold_out_products']   = 'Include products with 0 quantity:';
$_['entry_language']            = 'Language:';
$_['entry_currency']            = 'Currency:';
//$_['entry_shipping']    = 'Shipping flat rate:';

// Help
$_['help_status']               = 'Enables/Disables the feed.';
$_['help_save_to_file']         = 'Saves the feed to a CSV file and loads it. If you have timeout errors, enable this setting.';
//other URL parameters: tax_rate additional_images redirect start limit
$_['help_feed_url']             = 'You can change the feed URL parameters to get feeds in different languages, currencies etc.:
<br />
<style>
.feed_url_info {
    width: 100%;
    display: block;
    overflow-x: auto;
}
.feed_url_info th, .feed_url_info td {
    border: 1px solid;
    padding: 2px 6px;
}
</style>
<table class="feed_url_info">
<tr>
<th>Languages (override):</th>
<th>Currencies (override):</th>
<th>Use taxes (override):</th>
<th>Include/Exclude products:</th>
<th>Include/Exclude categories:</th>
<th>Multistore:</th>
</tr>
<tr>
<td>&lang={language code}</td>
<td>&curr={currency code}</td>
<td>&tax={0 or 1}</td>
<td>&(in)exclude_product_id={product ids separated by comma}</td>
<td>&(in)exclude_category_id={category ids separated by comma}</td>
<td>Replace the shop domain.</td>
</tr>
</table>
<br />
Example: .../index.php?route=extension/feed/google_business_data<wbr>&lang=us&curr=EUR&include_product_id=42,30,47&tax=0';
$_['help_use_meta']             = 'If meta is not available on a product the visible front page title/description will be used instead.';
$_['help_clear_html']           = 'Removes HTML tags like div, styles... from the title and descriptions. Recommend to enable.';
$_['help_pid1']                 = 'Product ID used in the feed. For dynamic ads, this must be identical to your remarketing scripts.';
$_['help_pid2']                 = 'Optional second product ID used on the remarketing tag (dynx_itemid2). Select "Do not use" if you are not using a second product ID (recommend).';
$_['help_use_tax']              = 'When enabled, prices will include taxes.';
$_['help_image_cache']          = 'Select an image size format (larger is better). Disabling the image cache might speed up the feed creation, but the images will be used as they are, so without watermarks and without adjusting the image dimensions to the minimal feed requirements.';
$_['help_disabled_products']    = 'Include or exclude disabled products.';
$_['help_sold_out_products']    = 'Include or exclude sold out products.';
$_['help_language']             = 'Select the language used in the feed.';
$_['help_currency']             = 'Select the currency used in the feed.';

// Error
$_['error_permission']          = 'Warning: You do not have permission to modify the extension!';
?>
