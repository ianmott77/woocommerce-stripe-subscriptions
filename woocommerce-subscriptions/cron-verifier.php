<?php
require_once __DIR__.'/includes/ajax-functions/requires.php';
$pc = new PlanController();
foreach ($pc->getAllPlans() as $plan) {
	$sc = new SubscriptionController($plan->plan);
	$localSubs = $sc->getAllSubscriptions();
	foreach ($localSubs  as  $localSub) {
		$stripeSubscription = $sc->getStripeSubscription($localSub->token);
		if($stripeSubscription instanceof WP_Error){
			$errors = $stripeSubscription;
			if(count($errors->errors['id']) < 2){
				if(preg_match('/(No such subscription)/', $errors->errors['id'][0])){
					if($sc->deleteLocalSubscription($localSub) > 0){
						$post = get_post($plan->plan);
						wp_mail(get_userdata($localSub->customerID)->email, 'Subscription Cancelled!', 'Your subscription to '.$post->post_title.'has been cancelled!');
					}
				}
			}
		}
	}
}
?>
