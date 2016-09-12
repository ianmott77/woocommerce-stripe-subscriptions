<?php
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
require_once( $parse_uri[0] . '/wp-content/plugins/woocommerce/woocommerce.php' );
require_once dirname(__DIR__).'/lib/PlanController.php';?>