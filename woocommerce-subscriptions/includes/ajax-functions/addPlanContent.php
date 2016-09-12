<?php 
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
require_once( $parse_uri[0] . '/wp-content/plugins/woocommerce/woocommerce.php' );
require_once dirname(__DIR__).'/lib/PlanController.php';
$pc = new PlanController();
$planID = null;
if(isset($_GET['plan_id'])){
	$planID = $_GET['plan_id'];
}
$json = file_get_contents('php://input');
$arr = json_decode($json);
$plan = $pc->getPlan($planID);
$plan->content = $json;
$result = $pc->updatePlanLocal($plan, $plan->plan);
echo $result;?>