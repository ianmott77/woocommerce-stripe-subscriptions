<div class="notification-area hide"></div>
<div class="subscription-content-wrapper">
<?php 
	require_once dirname(__DIR__).'/includes/lib/SubscriptionsTableController.php'; 
	$st = new SubscriptionsTableController();
	$subs = $st->getSubscriptionsForCustomer(get_current_user_id());
	wp_enqueue_style('subscription-account', plugin_dir_url(__DIR__).'css/account-subscriptions.css');
	wp_enqueue_script('AJAX', plugin_dir_url(__DIR__).'js/AJAX.js');
	foreach ($subs as $subscription) {
		$post = get_post($subscription->planID);?>
		<div class="panel panel-default subscription-wrapper unsubscribe_<?php echo $subscription->id ?>">
  			<div class="panel-heading subscription-title" data-toggle="collapse" data-target="#subscription_<?php echo $subscription->id ?>">
  				<span class="title-triangle"></span>
  				<?php echo $post->post_title.' - '.$post->post_date ?>
  			</div>
  			<div class="panel-body collapse subscription-info container-fluid" id="subscription_<?php echo $subscription->id ?>">
  				<table class="table subscription-info-table">
  					<tbody>
  						<tr>
    						<td><span><h4>Quantity</h4> : <?php echo $subscription->quantity ?></span></td>
    						<td><button class="btn btn-danger unsubscribe " type="button" value="Unsubscribe" data-plan-id="<?php echo $subscription->planID ?>" data-subscription="<?php echo $subscription->id ?>">Unsubscribe</button></td>
    					</tr>
    				</tbody>
    			</table>
  			</div>
		</div>
	<?php
	}?>
</div>
<script type="text/javascript">
	var notify = function(result){
			var note = $('.notification-area');
			if(result > 0){
				note.html('<div class="alert alert-success fade in out" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Subscription was deleted successfully!</strong></div>');
			}else{
				note.html('<div class="alert alert-warning fade in out" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>There was an error deleteing your subscription please contact us for further assistance</strong></div>');
			}
			note.removeClass('hide');
			$('.alert.close').click(function(e){
				$(this).parent().alert('close');
			});

	}

	$('.unsubscribe').click(function(){
		var self = $(this);
		var $id = self.attr('data-subscription');
		var $plan = self.attr('data-plan-id');
		var ajax = new AJAX('<?php echo plugin_dir_url(__DIR__) ?>includes/ajax-functions/deleteSubscription.php', 'subscription='+$id+'&'+'plan_id='+$plan, null,null, function(response){
				var result = parseInt(response, 10);
				console.log(response);
				if(result > 0){
					$('.unsubscribe_'+$id).remove();
					console.log('subscription Removed');
				}
				notify(result);
			}
		);
		ajax.request();
	});
</script>