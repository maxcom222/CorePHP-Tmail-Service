<?php defined("APP") or die() // Settings Page ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
  	<?php echo $this->ads(728) ?>
		<?php echo Main::message() ?>  			
		<div class="main-content panel panel-default panel-body">
			<h3><?php echo e("Account Settings") ?></h3>

			<?php if(!empty($this->user->auth)): ?>
				<div class="alert alert-warning"><?php echo e("You have used a social network to login. Please note that in this case you don't have a password set.") ?></div>
			<?php endif ?>

			<?php if(empty($this->user->username)): ?>
				<div class="alert alert-warning"><?php echo e("You have used a social network to login. You will need to choose a username.") ?></div>
			<?php endif ?>

			<form action="<?php echo Main::href("user/settings") ?>" role="form" class="form-horizontal" method="post">
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Email")?></label>			
					<div class="col-sm-9">
						<input type="text" value="<?php echo $this->user->email?>" name="email" class="form-control" />
						<?php if($this->config["user_activate"]): ?>
							<p class="help-block"><?php echo e("Please note that if you change your email, you will need to activate your account again.") ?></p>
						<?php endif; ?>
					</div>
        </div>
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Username")?></label>			
					<div class="col-sm-9">
						<input type="text" value="<?php echo $this->user->username?>" name="username" class="form-control"<?php echo (empty($this->user->username)?"":" disabled")?>/>
						<p class="help-block"><?php echo e("A username is required for your public profile to be visible.") ?></p>
					</div>
        </div>
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Password")?></label>
					<div class="col-sm-9">
						<input type="password" value="" name="password" class="form-control" />
						<p class="help-block"><?php echo ucfirst(e("leave blank to keep current one")) ?>.</p>
					</div>
        </div>
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Confirm Password")?></label>
					<div class="col-sm-9">
						<input type="password" value="" name="cpassword" class="form-control" />
						<p class="help-block"><?php echo ucfirst(e("leave blank to keep current one")) ?>.</p>
					</div>
        </div>
				<hr>
				<?php if($this->pro()): ?>
			  	<div class='form-group'>
		        <label for='description' class='col-sm-3 control-label'><?php echo e("Default Redirection") ?></label>
		        <div class='col-sm-9'>
				      <select name='defaulttype'>
				        <option value='direct' <?php echo ($this->user->defaulttype == "direct" || $this->user->defaulttype== "" ?" selected":"") ?>> <?php echo e('Direct') ?></option>
				        <option value='frame' <?php echo ($this->user->defaulttype == "frame"?" selected":"") ?>> <?php echo e('Frame') ?></option>
				        <option value='splash' <?php echo ($this->user->defaulttype == "splash"?" selected":"") ?>> <?php echo e('Splash') ?></option>
				        <option value='overlay' <?php echo ($this->user->defaulttype == "overlay"?" selected":"") ?>> <?php echo e("Overlay") ?></option>
							</select>		              
		        </div>
		      </div>			
					<hr>		      
				<?php endif; ?>
				<ul class="form_opt" data-id="public">
					<li class="text-label"><?php echo e("Profile Access")?>
					<small><?php echo e("Public profile will be activated only when this option is public. Username is required.")?></small>
					</li>
					<li><a href="" class="last<?php echo (!$this->user->public?" current":"")?>" data-value="0"><?php echo e("Private")?></a></li>
					<li><a href="" class="first<?php echo ($this->user->public?" current":"")?>" data-value="1"><?php echo e("Public")?></a></li>
				</ul>
				<input type="hidden" name="public" id="public" value="<?php echo $this->user->public ?>">

				<ul class="form_opt" data-id="media">
					<li class="text-label"><?php echo e("Media Gateway")?>
					<small><?php echo e("If enabled, special pages will be automatically created for your media URLs")?> (e.g. youtube, vimeo, dailymotion...)</small>
					</li>
					<li><a href="" class="last<?php echo (!$this->user->media?" current":"")?>" data-value="0"><?php echo e("Disabled")?></a></li>
					<li><a href="" class="first<?php echo ($this->user->media?" current":"")?>" data-value="1"><?php echo e("Enabled")?></a></li>
				</ul>
				<input type="hidden" name="media" id="media" value="<?php echo $this->user->media?>">
				<?php echo Main::csrf_token(TRUE) ?>
				<button type="submit" class="btn btn-primary"><?php echo e("Update")?></button>			   
			</form>
		</div>	
  </div><!--/#user-content-->
  <div id="widgets" class="col-md-4">
  	<?php echo $this->sidebar() ?>	

		<div class="panel panel-default panel-body">
			<h3><?php echo e("Activate Dark Mode") ?></h3>
			<p><?php echo e("Enable dark mode if you would like more contrast. This only applies to the dashboard.") ?></p>
			<?php if (isDark()): ?>
				<p><a href="?darkmode=off" class="btn btn-primary"><?php echo e("Disable Dark Mode") ?></a></p>
			<?php else: ?>
				<p><a href="?darkmode=on" class="btn btn-primary"><?php echo e("Activate Dark Mode") ?></a></p>				
			<?php endif ?>
		</div>

		<?php echo $this->widgets("export") ?>
		<?php if($this->config["allowdelete"]): ?>
			<div class="panel panel-default panel-body">
				<h3><?php echo e("Delete your account") ?></h3>
				<p><?php echo e("We respect your privacy and as such you can delete your account permanently and remove all your data from our server. Please note that this action is permanent and cannot be reversed.") ?></p>
				<p><a href="" class="btn btn-danger ajax_call" data-action="delete_account" data-title="<?php echo e("Delete your account") ?>"><?php echo e("Delete permanently") ?></a></p>
			</div>
		<?php endif ?>  			
  </div><!--/#widgets-->
</div><!--/.row-->