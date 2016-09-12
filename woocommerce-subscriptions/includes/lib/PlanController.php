<?php
require_once __DIR__.'/Plan.php';
require_once __DIR__.'/SubscriptionController.php';

/**
* 
*/
class PlanController{
	
		
	public $db;
	public function __construct(){
		global $wpdb;
		$this->db = $wpdb;
		$this->tableName = 'wp_woocommerce_subscriptions_plans';
	}

	public static function createTable(){
		global $wpdb;
		$table_name = 'wp_woocommerce_subscriptions_plans';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
  			id mediumint(9) NOT NULL AUTO_INCREMENT,
			plan mediumint(9) NOT NULL,
			registered tinyint(1) NOT NULL DEFAULT 0,
			content BLOB NULL,
  			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function getPlan($plan, $array = false){
		return $this->getPlanBy('plan', $plan, $array);
	}

	public function getAllPlans(){
		$planA = $this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions_plans', ARRAY_A);
		$plans = [];
		foreach ($planA as $key => $value) {
			$plans[] = $this->createPlan($value);
		}
		return $plans;
	}
	public function getPlanBy($by, $val, $array){
		$returns = [];
		foreach ($this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions_plans WHERE '.$by.' = '.$val, ARRAY_A) as $key =>$value){
			$returns[] = $this->createPlan($value);
		}
		if(count($returns) < 2 && !$array){
			$returns = $returns[0];
		}
		return $returns;

	}

	public function registerPlanLocal($plan){
		return $this->db->insert( 
			$this->tableName, 
				array( 
					'plan' => $plan->plan, 
					'registered' => $plan->registered,
					'content' => $plan->content
				)
			);
	}

	public function updatePlanLocal($plan, $planID){
		return $this->db->update( 
			$this->tableName, 
				array( 
					'plan' => $plan->plan, 
					'registered' => $plan->registered,
					'content' => $plan->content
				),
				array(
					'plan' => $planID
				)
			);
	}
	public function savePlan($plan){
		if($plan instanceof Plan){
			$this->registerPlan($plan);
			return $this->registerPlanLocal($plan);
		}else{
			$this->savePlan($this->createPlan($plan));
		}
	}

	public function registerPlan($plan){
		if($plan instanceof Plan){
			$product = $plan->product;
		}else if($plan instanceof WC_Product_Subscription){
			$product = $plan;
		}
		return WC_Stripe_API::request(
  		array(
        	'amount' => $product->get_price() * 100, 
        	'interval' => 'month',
        	'name' => $product->post->post_title,
        	'currency' => get_woocommerce_currency(),
        	'id' => $product->post->ID
        	),
        'plans'
  		);
	}

	public static function deletePlan($planID){
		return WC_Stripe_API::request(array(),'plans/'.$planID, 'DELETE');
	}

	public static function getStripePlan($id){
		return WC_Stripe_API::request(array(),'plans/'.$id);
	}

	public function createPlan($arr){
		return new Plan($arr['plan'], (isset($arr['registered'])) ? $arr['registered'] : 0, (isset($arr['content'])) ? $arr['content'] : null, (isset($arr['id'])) ? $arr['id'] : 0);
	}

	public function updateName($id){
		$post = get_post($id);
		return WC_Stripe_API::request(array('name' => $post->post_title),'plans/'.$id);
	}

	public function updatePlan($plan){
		if($plan instanceof Plan){
			$stripePlan = $this->getStripePlan($plan->plan);
			$product = $plan->product;
			$post = $product->post;

  			if($stripePlan->name != $post->post_title){
  				$stripePlan = $this->updateName($plan->plan);
  			}

  			if($stripePlan->amount != ($product->get_price() * 100)){
  				$sc = new SubscriptionController($plan->plan);
  				$subscriptions = $sc->getAllSubscriptions();
  				$this->deletePlan($plan->plan);
  				$this->registerPlan($plan);
  				$responses = [];
  				for($i = 0; $i < count($subscriptions); $i++){
  					$responses[] = $sc->updateSubscription($subscriptions[$i]);
  				}
  			}
  			$responses[] = $stripePlan;
  			return $responses;
		}else{
			$this->updatePlan($this->getPlan($plan));
		}
	}

	public function synchPlan($id){
		$plan = $this->getPlan($id);
		if(empty($plan) || !isset($plan)){ //new plan
  			$this->savePlan(array('plan' => $id, 'registered' => 1));
  		}else{ // editing a plan
  			$this->updatePlan($plan);
  		}	
	}
}