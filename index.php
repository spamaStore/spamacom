<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
// Version
define('VERSION', '3.0.3.1');

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('catalog');


