<?php

class Plan {
	public $plan;
	public $registered;
	public $id;
	public $content;
	public function __construct($plan, $registered = false, $content = null, $id = null){
		$this->plan = $plan;
		$this->registered = $registered;
		$this->id = $id;
		$this->content = $content;
		$this->product = wc_get_product($plan);
	}
}
?>
