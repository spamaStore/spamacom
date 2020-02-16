<?php
// HTTP
define('HTTP_SERVER', 'http://spama.local/admin/');
define('HTTP_CATALOG', 'http://spama.local/');

// HTTPS
define('HTTPS_SERVER', 'http://spama.local/admin/');
define('HTTPS_CATALOG', 'http://spama.local/');

// DIR
define('DIR_APPLICATION', '/var/www/html/spamacom/admin/');
define('DIR_SYSTEM', '/var/www/html/spamacom/system/');
define('DIR_IMAGE', '/var/www/html/spamacom/image/');
define('DIR_STORAGE', '/var/www/spamacomdata/storage/');
define('DIR_CATALOG', '/var/www/html/spamacom/catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_DATABASE', 'spamacom');
define('DB_PORT', '3306');
define('DB_PREFIX', 'ocv6_');

// OpenCart API 
define('OPENCART_SERVER', 'https://www.opencart.com/');