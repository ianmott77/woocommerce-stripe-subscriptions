<?php
require_once __DIR__.'/PlanContentController.php';

class PlanContentTableController extends PlanContentController{

	public $db;
	public $tableName;
	public $parentConstructed;
	public function __construct(){
		global $wpdb;
		$this->db = $wpdb;
		$this->tableName = 'wp_woocommerce_subscriptions_plan_content';
		$this->parentConstructed = false;
	}

	public static function createTable(){
		global $wpdb;
		$table_name = 'wp_woocommerce_subscriptions_plan_content';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
  			id mediumint(9) NOT NULL AUTO_INCREMENT,
			plan mediumint(9) NOT NULL,
			content mediumint(9) NOT NULL,
  			UNIQUE KEY id (id)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function constructParent($planID = null){
		parent::__construct($planID);
		$this->parentConstructed = true;
	}

	public function isParentConstucted(){
		return $parentConstructed;
	}

	public function getPlanContent($arr){
		$str .= 'WHERE ';
		$n = count($arr);
		$i = 0;
		$content = [];
		$planID = (isset($arr['plan']) && !empty($arr['plan'])) ? $arr['plan'] : null;
		if(!$this->parentConstructed)
				$this->constructParent($planID);
		foreach ($arr as $key => $value) {
			$str .= ($i+1 >= $n) ? $key.' = '.$value :  $key.' = '.$value .' AND ';
			$i++;
		}
		foreach ($this->db->get_results( 'SELECT * FROM '.$this->tableName.' '.$str, ARRAY_A) as $key => $value){
			$content[] = parent::createPlanContent($value);
		}
		return $content;
	}

}
?>