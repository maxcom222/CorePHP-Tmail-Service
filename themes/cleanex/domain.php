<?php defined("APP") or die() // Settings Page ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
  	<?php echo $this->ads(728) ?>
		<?php echo Main::message() ?>  			
		<div class="main-content panel panel-default panel-body">
			<?php if (!$this->isTeam() || ($this->isTeam() && $this->teamPermission("domain.create"))): ?>
				<h3><?php echo e("Add a custom domain") ?></h3>
				<form action="<?php echo Main::href("user/domain") ?>" method="post" role="form">
					<div class="row">
		        <div class="col-sm-6">
				      <div class="form-group">
				        <label for="domain" class="control-label"><?php echo e("Custom domain name") ?></label>
			          <input type="text" class="form-control" name="domain" id="domain" value="" placeholder="e.g. http://on.domain.com">
			          <p class="help-block"><?php echo e("You will need to setup a DNS record for your domain to work. See instructions on the right side.") ?></p>
			        </div>		        	
						</div>
						<div class="col-sm-6">
				      <div class="form-group">
				        <label for="default" class="control-label"><?php echo e("Root Redirect") ?></label>
			          <input type="text" class="form-control" name="default" id="default" value="" placeholder="e.g. http://yoursite.com">
			          <p class="help-block"><?php echo e("Redirects to this page if someone visits the root domain above without a short alias.") ?></p>
			        </div>		        	
						</div>
		      </div>
		      <?php echo Main::csrf_token(TRUE) ?>
		      <input type="submit" value="<?php echo e("Add Domain") ?>" class="btn btn-primary" />
	      </form>
				<hr>				
			<?php endif ?>
			<h3><?php echo e("Domain Names") ?></h3>
	    <div class="table-responsive">
	        <table class="table table-striped">
	          <thead>
	            <tr>
	              <th><?php echo e("Domain Name") ?></th>
	              <th><?php echo e("Root Redirect") ?></th>
	              <th><?php echo e("Status") ?></th>
	              <th></th>
	            </tr>
	          </thead>
	          <tbody>
	            <?php foreach ($domains as $domain): ?>
	              <tr data-id="<?php echo $domain->id ?>">
	                <td><?php echo $domain->domain ?></td>	               
	                <td><?php echo $domain->redirect ? $domain->redirect : "N/A" ?></td>	               
	                <td><?php echo $domain->status ? "<span class='label label-success'>".e("Active")."</span>" : "<span class='label label-danger'>".e("Inactive")."</span>" ?></td>         
	                <td>
	                	<?php if (!$this->isTeam() || ($this->isTeam() && $this->teamPermission("domain.delete"))): ?>
		                  <a href="<?php echo Main::href("user/domain/{$domain->id}").Main::nonce("delete_domain-{$domain->id}") ?>" class="btn btn-default btn-xs delete"><i class="fa fa-trash"></i></a>
		                <?php endif ?>
	                </td>
	              </tr>      
	            <?php endforeach ?>
	          </tbody>
	        </table> 
	    </div>     
		</div>	
  </div><!--/#user-content-->
  <div id="widgets" class="col-md-4">
  	<?php echo $this->sidebar() ?>
		<?php echo $widgets ?>				
  </div><!--/#widgets-->
</div><!--/.row-->