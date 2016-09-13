<?php 
require_once __DIR__.'/requires.php';
$pc = new PlanController();
$planID = null;
if(isset($_GET['plan_id'])){
	$planID = $_GET['plan_id'];
	$pcc = new PlanContentController($planID);
	$json = file_get_contents('php://input');
	$arr = json_decode($json);
	$registered = [];
	$planContents = [];
	$i = 0;
	foreach ($arr as $newPlan) {
		$planContents[$i] = $pcc->getBy('content', $newPlan->id, false);
		if(!($planContents[$i] instanceof PlanContent) && !is_array($planContents[$i])){
			$result = $pcc->savePlanContent(array('content' => $newPlan->id));
			$planContents[$i] = $pcc->getBy('content', $newPlan->id, false);
		}
		$i++;
	}
	$allPlanContent = $pcc->getAll();
	//there are some entries to delete if they aren't the same size
	if(count($planContents) < count($allPlanContent)){ 
		foreach ($allPlanContent as $planCont) {
			$found = false;
			foreach ($planContents as $newPlan) {
				if($planCont->content == $newPlan->content){
					$found = true;
					break;
				}
			}
			if(!$found){
				$result = $pcc->deletePlanContent($planCont);
			}
		}
	}
	echo $result;
}
?>