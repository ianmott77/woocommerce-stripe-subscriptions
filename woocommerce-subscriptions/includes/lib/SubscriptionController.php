<?php
require_once __DIR__.'/Subscription.php';
/**
* 
*/
class SubscriptionController{

	public $planID;
	public $db;
	public function __construct($planID){
		global $wpdb;
		$this->planID = $planID;
		$this->db = $wpdb;
	}


	public static function createTable(){
		global $wpdb;
		$table_name = 'wp_woocommerce_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
  			id mediumint(9) NOT NULL AUTO_INCREMENT,
			token varchar(50) NOT NULL,
			customer_id mediumint(9) NOT NULL,
			customer_token varchar(50) NOT NULL,
			plan_id mediumint(9) NOT NULL,
			quantity mediumint(9) NOT NULL DEFAULT 1,
  			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function getSubscriptionById($id, $array = false){
		return $this->getSubscriptionBy('id', $id, $array);
	}

	public function getSubscriptionByToken($token, $array = false){
		return $this->getSubscriptionBy('token',$token, $array);
	}

	public function getSubscriptionBy($by, $val, $array){
		$returns = [];
		foreach ($this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions WHERE '.$by.' = '.$val.' AND plan_id = '.$this->planID, ARRAY_A) as $key => $value){
			$returns[] = $this->createSubscription($value);
		}
		if(count($returns) < 2 && !$array){
			$returns = $returns[0];
		}
		return $returns;
	}
	public function getAllSubscriptions(){
		$subs = $this->db->get_results( 'SELECT * FROM wp_woocommerce_subscriptions WHERE plan_id = '.$this->planID, ARRAY_A);
		$subscriptions = [];
		foreach ($subs as $key => $value) {
			$subscriptions[] = $this->createSubscription($value);
		}
		return $subscriptions;
	}

	public function getStripeSubscriptions($limit = 10, $startingAfter = null){
		$request = 'subscriptions?limit='.$limit;
		if($startingAfter != null){
			$request .= '&starting_after='.$startingAfter;
		}
		return WC_Stripe_API::request(array('plan' => $this->planID,), $request, 'GET');
	}

	public function getStripeSubscription($token){
		return WC_Stripe_API::request(array(), 'subscriptions/'.$token, 'GET');
	}

	public function saveSubscription($subscription){
		if($subscription instanceof Subscription){
			return $this->db->insert( 
				'wp_woocommerce_subscriptions', 
				array( 
					'token' => $subscription->token, 
					'customer_id' => $subscription->customerID,
					'customer_token' => $subscription->customerToken,
					'quantity' => $subscription->quantity,
					'plan_id' => $this->planID,
				)
			);
		}else{
			return $this->saveSubscription($this->createSubscription($subscription));
		}
	}

	public function updateSubscription($subscription){
		WC_Stripe_API::request(
  			array(
				'plan' => $this->planID ,
				'quantity' => $subscription->quantity
				) , 
			'subscriptions/'.$subscription->token
		);
	}

	public function updateSubscriptionLocal($subscription){
		return $this->db->update( 
			'wp_woocommerce_subscriptions', 
				array( 
					'quantity' => $subscription->quantity,
				),
				array(
					'id' => $subscription->id
				)
			);
	}
	
	public function deleteSubscription($subscription){
		WC_Stripe_API::request(array(),'subscriptions/'.$subscription->token, 'DELETE');
		return $this->deleteLocalSubscription($subscription);
	}

	public function deleteLocalSubscription($subscription){
		return $this->db->delete( 
			'wp_woocommerce_subscriptions', 
				array(
					'id' => $subscription->id
				)
		);

	}

	public function synchSubscription($subscription){
		$this->updateSubscription($subscription);
		return $this->updateSubscriptionLocal($subscription);
	}

	public function createSubscription($arr){
		$planID = ($this->planID != null) ? $this->planID : (isset($arr['plan_id'])) ? $arr['plan_id'] : null;
		return new Subscription($arr['token'], $arr['customer_id'], $arr['customer_token'], (isset($arr['quantity'])) ? $arr['quantity'] : 1, $planID, (isset($arr['id'])) ? $arr['id'] : 0);
	}
}