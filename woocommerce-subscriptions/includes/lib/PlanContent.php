<?php
class PlanContent{
	public $plan;
	public $content;
	public $id;
	public function __construct($plan, $content, $id = null){
		$this->plan = $plan;
		$this->content = $content;
		$this->id = $id;
	}
}
?>