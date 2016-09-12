<?php
require_once __DIR__.'/requires.php';
$planID = null;
$sub = null;
$return = null;
if(isset($_GET['plan_id'])){
	$planID = $_GET['plan_id'];
}
if(isset($_GET['subscription'])){
	$sub = $_GET['subscription'];
}
if($planID != null && $sub != null){
	$sc = new SubscriptionController($planID);
	$sub = $sc->getSubscriptionById($sub);
	$json = file_get_contents('php://input');
	$obj = json_decode($json);
	$return = 0;
	if($sub->quantity != $obj->quantity){
		$sub->quantity = $obj->quantity;
		$return  = $sc->synchSubscription($sub);
	}
}
echo $return;
?>