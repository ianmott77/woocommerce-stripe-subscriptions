<?php
require_once __DIR__.'/SubscriptionController.php';
require_once __DIR__.'/PlanController.php';


class SubscriptionView{

	public $subscriptionController;
	public function __construct($subscriptionController){
		$this->subscriptionController = $subscriptionController;
	}

	public function subcriptionSubscribers(){ 
		$str = '<table class="table subscribers subscription_'.$this->subscriptionController->planID.'">';
		$i = 0;
		foreach ($this->subscriptionController->getAllSubscriptions() as $index => $subscription) { 
				if($i == 0){
					$str.='<tr>
						   <th>Subscription Token</th>
						   <th>Quantity</th>
						   <th>User Email</th>
						   <th>Save Subscription</th>
						   <th>Delete Subscription</th>
						   </tr>';
				}
				$str .= '<tr id="subsciption_'.$subscription->id.'">';
				$str .= '<td>'.$subscription->token.'</td>';
				$str .= '<td><input type="number" class="subscription-quantity" data-plan-id="'.$this->subscriptionController->planID.'" data-subscription-id="'.$subscription->id.'" value="'.$subscription->quantity.'" max="25" min="1"></td>';
				$str .= '<td>'.get_user_by('id', $subscription->customerID)->user_email.'</td>';
				$str .= '<td><button type="button" data-plan-id="'.$this->subscriptionController->planID.'" data-subscription-id="'.$subscription->id.'" class="btn btn-success subscriber-save-button">Save</button></td>';
				$str .= '<td><button type="button" data-plan-id="'.$this->subscriptionController->planID.'" data-subscription-id="'.$subscription->id.'" class="btn btn-danger subscriber-delete-button">Delete</button></td>';

			$str .= '</tr>';
			$i++;
		}
		$str .= '</table>';
		return $str;
	}

	public function subscriptionContent(){
		$str = '';
		$str = '<div class="subscription-content container-fluid">';
		$str .= '<div role="tabpanel" class="tab-pane add-sub-content container-fluid" id="'.$this->subscriptionController->planID.'-add-content">';
		$query = new WP_Query(array('post_status' => 'publish'));
		$str .= $this->buildContent($query, 'Posts');
		$query = new WP_Query(array('post_status' => 'publish', 'post_type' => 'product'));
		$str .= $this->buildContent($query, 'Products');
		$query = new WP_Query(array('post_status' => 'publish', 'post_type' => 'page'));
		$str .= $this->buildContent($query, 'Pages');
		$str .= '<button type="button" data-plan-id="'.$this->subscriptionController->planID.'" class="btn btn-primary content-save-button">Save</button>';
		$str .= '</div>';
		return $str;
	}

	public function subscriptionContentJS(){ ?>
		<script>

		var notify = function(result){
			var note = jQuery('.notification-area');
			if(result > 0){
				note.html('<div class="alert alert-success fade in out" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Plan updated succesfully!</strong></div>');
			}else{
				note.html('<div class="alert alert-warning fade in out" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Plan was not updated nothing to update!</strong></div>');
			}
			note.removeClass('hide');
			jQuery('.alert.close').click(function(e){
				jQuery(this).parent().alert('close');
			});

		}

		jQuery('ul.nav a').click(function (e) {
  			e.preventDefault();
 			jQuery(this).tab('show');
		});

		jQuery('.content-save-button').click(function(e){
			var self = jQuery(this);
			var id = self.attr('data-plan-id');
			var checkBoxes = jQuery('.content-checkbox');
			var json = [];
			for(var i = 0; i < checkBoxes.length; i++){
				if(checkBoxes[i].checked){
					var id = jQuery(checkBoxes[i]).attr('data-post-id');
					var postType = jQuery(checkBoxes[i]).attr('data-post-type');
					var a = {
						postType : postType,
						id : id
					}
					json.push(a);
				}
			}
			var ajax = new AJAX('<?php echo plugin_dir_url(__DIR__)?>ajax-functions/addPlanContent.php','plan_id='+id, null, null, function(response){
					notify(parseInt(response, 10));
				}
			);
			ajax.uploadJSONRequest(json);
		});


		jQuery('.subscriber-save-button').click(function(e){
			var self = jQuery(this);
			var id = self.attr('data-plan-id');
			var sub = self.attr('data-subscription-id');
			var quantity = jQuery('#subsciption_'+sub+' td .subscription-quantity');
			var a = {
				quantity : quantity.val()
			}
			var ajax = new AJAX('<?php echo plugin_dir_url(__DIR__)?>ajax-functions/saveSubscription.php','plan_id='+id+'&subscription='+sub, null, null, function(response){
					notify(parseInt(response, 10));
			});
			ajax.uploadJSONRequest(a);
		});
		jQuery('.subscriber-delete-button').click(function(e){
			var self = jQuery(this);
			var id = self.attr('data-plan-id');
			var sub = self.attr('data-subscription-id');;
			var ajax = new AJAX('<?php echo plugin_dir_url(__DIR__)?>ajax-functions/deleteSubscription.php','plan_id='+id+'&subscription='+sub, null, null, function(response){
					var result = parseInt(response, 10);
					console.log(response);
					if(result > 0){
						jQuery('#subsciption_'+sub).remove();
					}
					notify(result);
			});
			ajax.request();
		});
		jQuery('.panel-body.collapse.post-table').on('show.bs.collapse', function(e){
			jQuery(this).siblings('.post-type-title').children('.title-triangle').css('transform', 'rotate(90deg)');
		});
		jQuery('.panel-body.collapse.post-table').on('hide.bs.collapse', function(e){
			jQuery(this).siblings('.post-type-title').children('.title-triangle').css('transform', 'rotate(0deg)');
		});
		</script>
	<?php
	}

	private function buildContent($query, $title){
		$str .= '<div class="panel panel-default">';
  
		$str .= '<div class="panel-heading post-type-title" data-toggle="collapse" data-target="#'.$title.'-'.$this->subscriptionController->planID.'"><span class="title-triangle"></span><h2>'.$title.'</h2></div>';
		$str .= '<div class="panel-body collapse post-table" id="'.$title.'-'.$this->subscriptionController->planID.'">';
		$str .= '<table class="table">';
		$i = 0;
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$pc = new PlanController();
				$post = get_post(get_the_ID());
				if($i == 0){
					$str .= '<tr>';
					$str .= '<th>Post Title</th>';
					$str .= '<th>Add To Subscription Content</th>';
					$str .= '</tr>';
				}
				$str .= '<tr>';
				$t = $post->post_title;
				$id = $post->ID;
				$plan = $pc->getPlan($this->subscriptionController->planID);
				$planContent = json_decode($plan->content);
				$checked = '';
				for($i =0; $i < count($planContent); $i++){
					if($planContent[$i]->id == $id){
						$checked = 'checked';
					}
				}
				$type = $post->post_type;
				$str .= '<td>'.$t.'</td>';
				$str .= '<td><input class="content-checkbox" data-post-id="'.$id.'" data-post-type="'.$type.'"" type="checkbox" name="'.strtolower($t).'" value="'.$t.'" '.$checked.'></td>';
				$str .= '</tr>';
				$i++;
			}
			wp_reset_postdata();
		} else {
			$str .= '<h3>No Posts In '.$title.'</h3>';
		}
		$str .= '</table>';
		$str .= '</div>';
		$str .= '</div>';

		return $str;
	}
}
?>