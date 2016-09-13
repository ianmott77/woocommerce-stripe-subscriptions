<?php
require_once __DIR__.'/PlanContentController.php';
class Plan {
	public $plan;
	public $registered;
	public $id;
	public $content;
	protected $planContentController;
	public function __construct($plan, $registered = false, $id = null){
		$this->plan = $plan;
		$this->registered = $registered;
		$this->id = $id;
		$this->planContentController = new PlanContentController($this->plan);
		$this->content = $this->planContentController->getAll();
		$this->product = wc_get_product($plan);
	}
}
?>
