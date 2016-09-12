<?php

class WC_Gateway_Stripe_Subscription extends WC_Gateway_Stripe

{
	public

	function __construct()
	{
		$this->id = 'stripe-subscriptions';
		$this->method_title = __('Stripe', 'woocommerce-gateway-stripe-subscriptions');
		$this->method_description = __('Stripe works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'woocommerce-gateway-stripe-subscriptions');
		$this->has_fields = true;
		$this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
		$this->supports = array(
			'subscriptions',
			'products',
			'refunds',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change', // Subs 1.n compatibility
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'pre-orders',
			'tokenization',
		);

		// Load the form fields

		$this->init_form_fields();

		// Load the settings.

		$this->init_settings();
		// Get setting values.

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled = $this->get_option('enabled');
		$this->testmode = 'yes' === $this->get_option('testmode');
		$this->capture = 'yes' === $this->get_option('capture', 'yes');
		$this->stripe_checkout = 'yes' === $this->get_option('stripe_checkout');
		$this->stripe_checkout_locale = $this->get_option('stripe_checkout_locale');
		$this->stripe_checkout_image = $this->get_option('stripe_checkout_image', '');
		$this->saved_cards = 'yes' === $this->get_option('saved_cards');
		$this->secret_key = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('secret_key');
		$this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
		$this->bitcoin = 'USD' === strtoupper(get_woocommerce_currency()) && 'yes' === $this->get_option('stripe_bitcoin');
		$this->logging = 'yes' === $this->get_option('logging');
		if ($this->stripe_checkout) {
			$this->order_button_text = __('Continue to payment', 'woocommerce-gateway-stripe');
		}

		if ($this->testmode) {
			$this->description.= ' ' . sprintf(__('TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the documentation "<a href="%s">Testing Stripe</a>" for more card numbers.', 'woocommerce-gateway-stripe') , 'https://stripe.com/docs/testing');
			$this->description = trim($this->description);
		}

		WC_Stripe_API::set_secret_key($this->secret_key);

		// Hooks
		add_action('wp_enqueue_scripts', array(
			$this,
			'payment_scripts'
		));
		add_action('admin_notices', array(
			$this,
			'admin_notices'
		));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		));
		$this->temp = 'stripe-subscriptions';

	}

	protected
	function generate_possible_subscription_payment_request($order, $source)
	{
		$subscriptions = [];
		$totalSubPrice = 0;
		$quantities = [];
		$post_data = $this->generate_payment_request($order, $source);
		$subCount = 0;
		foreach($order->get_items() as $key => $value) {
			if (is_array($value)) {
				foreach($value as $prop => $val) {
					if ($prop === 'item_meta') {
						foreach($val as $metaKey => $metaArray) {
							if ($metaKey === '_product_id') {
								foreach($metaArray as $prodKey => $id) {
									$product = wc_get_product($id);
									if ($product->get_type() === 'subscription') {
										$subscriptions[$subCount] = $product;

										// calculate the price of all the subscriptions in the order
										$quantities[$subCount] = $val['_qty'][$prodKey];
										$totalSubPrice += $product->get_price() * $quantities[$subCount];
										$subCount++;
									}
								}
							}
						}
					}
				}
			}
		}

		if (is_array($subscriptions)) {

			// get the customer for the subscriptions
			$customer = $post_data['customer'];

			// loop through all the subscriptions on the order
			for ($i = 0; $i < count($subscriptions); $i++) {

				// create a subscription request to the Stripe API on the current subscription
				$response = WC_Stripe_API::request(array(
					'customer' => $customer,
					'plan' => $subscriptions[$i]->get_id() ,
					'quantity' => $quantities[$i],
				) , 'subscriptions');

				//save a subscription in the database
				$s = new SubscriptionController($subscriptions[$i]->get_id());
				$s->saveSubscription(
					array(
						'token' => $response->id, 
						'customer_id' => wp_get_current_user()->ID,
						'customer_token' => $customer,
						'quantity' => $quantities[$i]
					)
				);
			}
		}

		//set the proper amount to charge by reducing the amount by however much the cost of the subscriptions were
		$post_data['amount'] = $this->get_stripe_amount(($order->get_total() - $totalSubPrice) , $post_data['currency']);
		return $post_data;
	}

	public

	function process_payment($order_id, $retry = true, $force_customer = false)
	{
		try {
			$order = wc_get_order($order_id);
			$source = $this->get_source(get_current_user_id() , $force_customer);
			if (empty($source->source) && empty($source->customer)) {
				$error_msg = __('Please enter your card details to make a payment.', 'woocommerce-gateway-stripe');
				$error_msg.= ' ' . __('Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'woocommerce-gateway-stripe');
				throw new Exception($error_msg);
			}

			// Store source to order meta

			$this->save_source($order, $source);

			// Handle payment

			if ($order->get_total() > 0) {
				if ($order->get_total() * 100 < 50) {
					throw new Exception(__('Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce-gateway-stripe'));
				}

				WC_Stripe::log("Info: Begin processing payment for order $order_id for the amount of {$order->get_total() }");
				$post_data = $this->generate_possible_subscription_payment_request($order, $source);

				// Make the request

				if ($post_data['amount'] > 0) {
					$response = WC_Stripe_API::request($post_data);
				}

				if (is_wp_error($response)) {

					// Customer param wrong? The user may have been deleted on stripe's end. Remove customer_id. Can be retried without.

					if ('customer' === $response->get_error_code() && $retry) {
						delete_user_meta(get_current_user_id() , '_stripe_customer_id');
						return $this->process_payment($order_id, false, $force_customer);

						// Source param wrong? The CARD may have been deleted on stripe's end. Remove token and show message.

					}
					elseif ('source' === $response->get_error_code() && $source->token_id) {
						$token = WC_Payment_Tokens::get($source->token_id);
						$token->delete();
						throw new Exception(__('This card is no longer available and has been removed.', 'woocommerce-gateway-stripe'));
					}
					throw new Exception($response->get_error_code() . ': ' . $response->get_error_message());
				}

				// Process valid response

				$this->process_response($response, $order);
			}
			else {
				$order->payment_complete();
			}

			// Remove cart

			WC()->cart->empty_cart();

			// Return thank you page redirect

			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url($order)
			);
		}

		catch(Exception $e) {
			wc_add_notice($e->getMessage() , 'error');
			WC()->session->set('refresh_totals', true);
			WC_Stripe::log(sprintf(__('Error: %s', 'woocommerce-gateway-stripe') , $e->getMessage()));
			return;
		}
	}

	/**
	 * Payment form on checkout page
	 */
	public

	function payment_fields()
	{
		$user = wp_get_current_user();
		$display_tokenization = $this->supports('tokenization') && is_checkout() && $this->saved_cards && $user->ID;
		if ($user->ID) {
			$user_email = get_user_meta($user->ID, 'billing_email', true);
			$user_email = $user_email ? $user_email : $user->user_email;
		}
		else {
			$user_email = '';
		}

		if (is_add_payment_method_page()) {
			$pay_button_text = __('Add Card', 'woocommerce-gateway-stripe');
		}
		else {
			$pay_button_text = '';
		}

		echo '<div
				id="stripe-payment-data"
				data-panel-label="' . esc_attr($pay_button_text) . '"
				data-description=""
				data-email="' . esc_attr($user_email) . '"
				data-amount="' . esc_attr($this->get_stripe_amount(WC()->cart->total)) . '"
				data-name="' . esc_attr(sprintf(__('%s', 'woocommerce-gateway-stripe') , get_bloginfo('name', 'display'))) . '"
				data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '"
				data-image="' . esc_attr($this->stripe_checkout_image) . '"
				data-bitcoin="' . esc_attr($this->bitcoin ? 'true' : 'false') . '"
				data-locale="' . esc_attr($this->stripe_checkout_locale ? $this->stripe_checkout_locale : 'en') . '">';
		if ($this->description) {
			echo apply_filters('wc_stripe_description', wpautop(wp_kses_post($this->description)));
		}

		if ($display_tokenization) {
			//$this->id = 'stripe';
			$this->tokenization_script();
			$this->saved_payment_methods();
		}

		if (!$this->stripe_checkout) {
			$this->form();
			if ($display_tokenization) {
				$this->save_payment_method_checkbox();
			}
		}

		echo '</div>';
	}
}

?>
