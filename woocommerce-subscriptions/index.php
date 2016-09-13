<?php
/**
 * Plugin Name: WooCommerce Subscriptions
 * Version: 0.0.1
 * Author: Ian Mott
 * Author URI: http://ianmott.info
 */
require_once __DIR__.'/includes/lib/SubscriptionView.php';
require_once __DIR__.'/includes/lib/SubscriptionsTableController.php';
require_once __DIR__.'/includes/lib/PlanController.php';
require_once __DIR__.'/includes/lib/PlanContentTableController.php';


function register_woo_subscription_type(){
	class WC_Product_Subscription extends WC_Product_Simple{
		public function __construct($product){
			parent::__construct($product);
			$this->product_type = 'subscription';
		}
	}
}

function add_product_subscription( $types ){
	// Key should be exactly the same as in the class product_type parameter
	$types[ 'subscription' ] = __( 'Subscription', 'woocommerce' );
	return $types;
}

function init_woo_subscriptions(){
	add_submenu_page( 'edit.php?post_type=product', 'Subscription', 'Subscriptions', 'manage_options' ,'subscriptions', function(){
		wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
		wp_enqueue_style('wc-subs', plugin_dir_url(__FILE__).'css/style.css');
		wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array(), false, true);
		wp_enqueue_script('ajax', plugin_dir_url(__FILE__).'js/AJAX.js');
		$pc = new PlanController();
		$plans = $pc->getAllPlans();?>
		<div class="notification-area hide"></div>
		<h1>WooCommerce Subscriptions</h1>
		<div class="subscription-wrapper container-fluid">
		<?php
		$tabs = '<ul class="nav nav-pills nav-stacked plan-list"  role="tablist">';
		$content = '<div class="tab-content plan-content">';
		$i = 0;
		foreach ($plans as $key => $plan) {
			$sc = new SubscriptionController($plan->plan);
			$sv = new SubscriptionView($sc);
			$class = ($i == 0) ? 'active' : '';
			$tabs .= '<li role="presentation" class="plan '.$class.'"><a href="#sub_plan_'.$plan->product->post->ID.'">'.$plan->product->post->post_title.'</a></li>';
			$content .= 
			'<div class="full-plan-content container-fluid tab-pane '.$class.'" role="tabpanel" id="sub_plan_'.$plan->product->post->ID.'">
				<h2>'.$plan->product->post->post_title.'</h2>
				<ul class="nav nav-tabs plan-content-settings"  role="tablist">
  					<li role="presentation" class="active"><a href="#subscriptions_'.$plan->product->post->ID.'">Subscriptions</a></li>
  					<li role="presentation"><a href="#settings_'.$plan->product->post->ID.'">Settings</a></li>
  					<li role="presentation"><a href="#content_'.$plan->product->post->ID.'">Subscription Content</a></li>
				</ul>
				<div class="tab-content">
    				<div role="tabpanel" class="tab-pane active" id="subscriptions_'.$plan->product->post->ID.'">'.$sv->subcriptionSubscribers().'</div>
    				<div role="tabpanel" class="tab-pane" id="settings_'.$plan->product->post->ID.'"></div>
   					<div role="tabpanel" class="tab-pane" id="content_'.$plan->product->post->ID.'">'.$sv->subscriptionContent().'</div>
				</div>
			</div>
			</div>';
			$i++;
		}
		$tabs .= '</ul>';
		$content .= '</div>';
		echo $tabs.$content;
		?>
		</div>
		<?php
		$sv->subscriptionContentJS();
	});
}

function show_price_fields(){ 
	if ( 'product' != get_post_type() ) :
			return;
	endif;
	?>
	<script>
		jQuery( document ).ready( function() {
			jQuery( '.options_group.pricing' ).addClass( 'show_if_subscription' ).show();
			jQuery( '.general_options' ).addClass( 'show_if_subscription' ).show();
			jQuery( '.inventory_options' ).addClass( 'show_if_subscription' ).show();
			jQuery( '#_virtual' ).parent().addClass( 'show_if_subscription' ).show();
			jQuery( '#_downloadable' ).parent().addClass( 'show_if_subscription' ).show();
		});
		</script>
	<?php
}

function synch_plan($id){
	$post = get_post($id);
	if($post->post_type == 'product'){
		$product = wc_get_product($id);
		if($product->get_type() == 'subscription'){
			$pc = new PlanController();
			$pc->synchPlan($id);		
  		}
	}
}

function register_subscription_gateway(){
	require_once __DIR__.'/includes/class-wc-product-subscription.php';
}

function add_subscription_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Stripe_Subscription'; 
	return $methods;
}

function subscription_add_to_cart(){
	wc_get_template( 'single-product/add-to-cart/simple.php' );
}

function install(){
	PlanController::createTable();
	SubscriptionController::createTable();
	PlanContentTableController::createTable();
}

function user_is_subscribed($id){
	$pctc = new PlanContentTableController();
	$contents = $pctc->getPlanContent(array('content' => $id));
	$notProtected = true;
	if(count($contents) > 0 && isset($contents) && !empty($contents)){
		$notProtected = false;
		$sct = new SubscriptionsTableController();
		foreach ($contents as  $content) {
			$subs = $sct->getSubscriptions(array('customer_id' => get_current_user_id(), 'plan_id' => $content->plan));
			if(count($subs) > 0){
				return true;
			}
		}
	}
	return $notProtected;
}

function check_availibility($content){
	$id = get_the_ID();
	if(!user_is_subscribed($id)){
		$content = 'This is protected content';
	}
	return $content;
}

function check_availibility_shop(){
	$id = (is_shop()) ? woocommerce_get_page_id('shop') :get_the_ID();
	if(!user_is_subscribed($id)){
		remove_all_actions('woocommerce_single_product_summary');
		remove_all_actions('woocommerce_before_single_product');
		remove_all_actions('woocommerce_after_single_product');
		remove_all_actions('woocommerce_before_single_product_summary');
		remove_all_actions('woocommerce_after_single_product_summary');
		remove_all_actions('woocommerce_archive_description');
		remove_all_filters('woocommerce_show_page_title');
		remove_all_actions('woocommerce_before_shop_loop');
		remove_all_actions('woocommerce_single_product_summary');
		remove_all_actions('woocommerce_after_shop_loop');
		remove_all_actions('woocommerce_after_main_content');
		remove_all_actions('woocommerce_sidebar');
		remove_all_actions('woocommerce_before_main_content');
		add_filter('wc_get_template_part', function($template, $slug, $name){
			remove_all_actions('woocommerce_single_product_summary');
			remove_all_actions('woocommerce_before_single_product');
			remove_all_actions('woocommerce_after_single_product');
			remove_all_actions('woocommerce_before_single_product_summary');
			remove_all_actions('woocommerce_after_single_product_summary');
			return null;
		}, 10, 3);
		wc_enqueue_js('jQuery(".page-title").addClass("hide")');
		echo 'This is protected content';
	}
}

function check_availibility_shop_item($template, $slug, $name){
	if($slug === 'content' && $name === 'product'){
		$id = get_the_ID();
		if(!user_is_subscribed($id)){
			$template = null;
			remove_all_actions('woocommerce_single_product_summary');
			remove_all_actions('woocommerce_before_single_product');
			remove_all_actions('woocommerce_after_single_product');
			remove_all_actions('woocommerce_before_single_product_summary');
			remove_all_actions('woocommerce_after_single_product_summary');
		}
	}
	return $template;
}

function fix_checout_page_php($located, $template_name, $args, $template_path, $default_path) {
	if(strpos($located, 'checkout/payment-method.php') !== false){
		if(is_array($args)){
			if($args['gateway']->id == 'stripe-subscriptions'){
				$args['gateway']->id = 'stripe';
			}
		}
	}else if(strpos($located, 'checkout/terms.php') !== false){
		$gateways = new WC_Payment_Gateways();
		foreach($gateways->get_available_payment_gateways( ) as $gateway){
			if($gateway->temp == 'stripe-subscriptions'){
				$gateway->id = 'stripe-subscriptions';
			}
		}
	}
	return $located;
}

function fix_checkout(){
	$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
	if(isset($_POST['payment_method'])){
		if($_POST['payment_method'] == 'stripe'){
			if(!isset( $available_gateways[ $_POST['payment_method'] ] )){
				if(isset( $available_gateways[ 'stripe-subscriptions'])){
					$_POST['payment_method'] = 'stripe-subscriptions';
				}
			}
		}
	}
}

//http://stackoverflow.com/questions/38039616/woocommerce-assigning-an-endpoint-to-a-custom-template-in-my-account-pages
function user_subscription_menu_tab($items){
	$items = array(
        'dashboard'       => __( 'Dashboard', 'woocommerce' ),
        'orders'          => __( 'Orders', 'woocommerce' ),
        'downloads'       => __( 'Downloads', 'woocommerce' ),
        'edit-address'    => __( 'Addresses', 'woocommerce' ),
        'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
        'edit-account'    => __( 'Edit Account', 'woocommerce' ),
        'subscriptions'   => __('Subscriptions', 'woocommerce-subscriptions'),
        'customer-logout' => __( 'Logout', 'woocommerce' ),
    );
	return $items;
}

function new_endpoint(){
	add_rewrite_endpoint('subscriptions', EP_ROOT | EP_PAGES);
}

function add_query_vars($vars){
	$vars[] = 'subscriptions';
	return $vars;
}

function flush_rewrite(){
	flush_rewrite_rules();
}

function subscriptions_account_page(){
	include __DIR__.'/templates/subscriptions_account.php';
}

register_activation_hook( __FILE__, 'install' );
add_action( 'woocommerce_account_subscriptions_endpoint', 'subscriptions_account_page' );
add_action('after_switch_theme', 'flush_rewrite');
add_filter('query_vars', 'add_query_vars', 0);
add_action('init', 'new_endpoint');
add_filter('woocommerce_account_menu_items', 'user_subscription_menu_tab');
add_action('woocommerce_checkout_process', 'fix_checkout');
add_filter('the_content', 'check_availibility');
add_action('woocommerce_before_single_product', 'check_availibility_shop');
add_action('woocommerce_before_main_content', 'check_availibility_shop');
add_filter('wc_get_template_part', 'check_availibility_shop_item', 10, 3);
add_filter('wc_get_template', 'fix_checout_page_php', 10, 5);
add_action('woocommerce_subscription_add_to_cart', 'subscription_add_to_cart');
add_filter('product_type_selector', 'add_product_subscription');
add_filter('woocommerce_payment_gateways', 'add_subscription_gateway' );
add_action('plugins_loaded', 'register_subscription_gateway');
add_action('save_post', 'synch_plan');
add_filter('admin_menu', 'init_woo_subscriptions');
add_filter('admin_footer', 'show_price_fields');
add_action('plugins_loaded', 'register_woo_subscription_type');