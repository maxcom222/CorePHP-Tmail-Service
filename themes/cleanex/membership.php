<?php defined("APP") or die() // Settings Page ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
  	<?php echo $this->ads(728) ?>
		<?php echo Main::message() ?>  			
		<?php echo $this->last_payments() ?>
  </div><!--/#user-content-->
  <div id="widgets" class="col-md-4">
  	<?php echo $this->sidebar() ?>  	
		<?php if($this->pro() && isset($this->config["pt"]) && $this->config["pt"] == "stripe"): ?>
			<div class="panel panel-default panel-body">
				<h3><?php echo e("Your Premium Membership") ?></h3>
				<?php $plan = $this->db->get("plans", ["id" => $this->user->planid], ["limit" => 1]) ?>
				<p><strong><?php echo e("Plan Name") ?></strong>: <?php echo $plan->name ?></p>							
				<p><strong><?php echo e("Last Payment") ?></strong>: <?php echo date("F d, Y", strtotime($this->user->last_payment)) ?></p>
				<p><strong><?php echo e("Expiry") ?></strong>: <?php echo date("F d, Y", strtotime($this->user->expiration)) ?></p>
				<hr>
				<h3><?php echo e("Cancel Membership") ?></h3>
				<p><?php echo e("You can cancel your membership whenever your want. Upon request, your membership will be canceled right before your next payment period. This means you can still enjoy premium features until the end of your membership.") ?></p>
				<p><a href="" class="btn btn-danger btn-round ajax_call" data-action="cancel" data-title="<?php echo e("Cancel Membership") ?>"><?php echo e("Cancel membership") ?></a></p>
			</div>
		<?php endif ?>  			
  </div><!--/#widgets-->
</div><!--/.row-->