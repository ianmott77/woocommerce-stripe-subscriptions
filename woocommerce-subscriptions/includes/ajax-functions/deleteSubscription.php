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
	$return = 0;
	$return = $sc->deleteSubscription($sub);
}
echo $return;
?>