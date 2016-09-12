<?php
require_once __DIR__.'/SubscriptionController.php';

class SubscriptionsTableController extends SubscriptionController{

	public $db;
	private $parentConstructed;
	public function __construct(){
		global $wpdb;
		$this->db = $wpdb;
		$this->tableName = 'wp_woocommerce_subscriptions';
		$this->parentConstructed = false;
	}

	public function constructParent($planID = null){
		parent::__construct($planID);
		$this->parentConstructed = true;
	}

	public function isParentConstucted(){
		return $parentConstructed;
	}

	public function getSubscriptionsForPlan($planID){
		if(!$this->parentConstructed){
			$this->constructParent($planID);
		}
		return parent::getAllSubscriptions();
	}

	public function getSubscriptions($arr){
		$str .= 'WHERE ';
		$n = count($arr);
		$i = 0;
		$subs = [];
		$planID = (isset($arr['plan_id']) && !empty($arr['plan_id'])) ? $arr['plan_id'] : null;
		if(!$this->parentConstructed)
				$this->constructParent($planID);
		foreach ($arr as $key => $value) {
			$str .= ($i+1 >= $n) ? $key.' = '.$value :  $key.' = '.$value .' AND ';
			$i++;
		}
		foreach ($this->db->get_results( 'SELECT * FROM '.$this->tableName.' '.$str, ARRAY_A) as $key => $value){
			$subs[] = parent::createSubscription($value);
		}
		return $subs;
	}

	public function getSubscriptionsForCustomer($id, $planID = null){
		return $this->getSubscriptions(array('customer_id' => $id));
	}
    
    public function getTableName(){
        return $this->tableName;
    }

}?>
