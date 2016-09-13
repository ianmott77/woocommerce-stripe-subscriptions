<?php
require_once __DIR__.'/PlanContent.php';

class PlanContentController{
	public $planID;
	public $tableName;
	public $db;
	public function __construct($planID){
		global $wpdb;
		$this->planID = $planID;
		$this->db = $wpdb;
		$this->tableName = 'wp_woocommerce_subscriptions_plan_content';
	}

	public function getBy($by, $val, $array){
		$returns = [];
		foreach ($this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions_plan_content WHERE '.$by.' = '.$val.' AND plan = '.$this->planID, ARRAY_A) as $key => $value){
			$returns[] = $this->createPlanContent($value);
		}
		if(count($returns) < 2 && !$array){
			$returns = $returns[0];
		}
		return $returns;
	}

	public function getAll(){
		$subs = $this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions_plan_content WHERE plan = '.$this->planID, ARRAY_A);
		$content = [];
		foreach ($subs as $key => $value) {
			$content[] = $this->createPlanContent($value);
		}
		return $content;
	}

	public function createPlanContent($arr){
		$planID;
		if($this->planID != null) {
			$planID = $this->planID;
		}else if(isset($arr['plan']) && !empty($arr['plan'])){
			$planID = $arr['plan'];
		}	
		return new PlanContent($planID, $arr['content'], (isset($arr['id'])) ? $arr['id'] : null);
	}

	public function savePlanContent($planContent){
		if($planContent instanceof PlanContent){
			return $this->db->insert( 
				'wp_woocommerce_subscriptions_plan_content', 
				array( 
					'plan' => $planContent->plan, 
					'content' => $planContent->content,
				)
			);
		}else{
			return $this->savePlanContent($this->createPlanContent($planContent));
		}
	}
	
	public function deletePlanContent($planContent){
		return $this->db->delete( 
			$this->tableName, 
				array(
					'id' => $planContent->id
				)
		);
	}
}