<?php
/**
* 
*/
class Subscription{
	public $token;
	public $customerID;
	public $customerToken;
	public $id;
	public $quantity;
	public $planID;
	public function __construct($token, $customerID, $customerToken, $quantity = 1, $planID = null, $id = 0){
		$this->token = $token;
		$this->customerID = $customerID;
		$this->customerToken = $customerToken;
		$this->id = $id;
		$this->quantity = $quantity;
		$this->planID = $planID; 
	}
}
?>