<?php defined("APP") or die() // Settings Page ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
  	<?php echo $this->ads(728) ?>
		<?php echo Main::message() ?>  			
		<div class="main-content"></div>
		<?php if (!$this->isTeam()): ?>
			<div class="panel panel-default panel-body">
				<h3><?php echo e("Invite Member") ?></h3>
				<form action="<?php echo Main::href("user/teams/add") ?>" method="post">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
				    		<label for="email" class="label-control"><?php echo e("Email") ?></label>
				    		<input type="email" value="" name="email" class="form-control" placeholder="johndoe@email.tld" />				
							</div>						
						</div>
						<div class="col-md-6">
							<div class="form-group">
				    		<label for="permissions" class="label-control"><?php echo e("Permissions") ?></label>
				    		<select name="permissions[]" class="form-control" data-placeholder="<?php echo e("Permissions") ?>" multiple>	
				    			<optgroup label="<?php echo e("Links") ?>">
										<option value="links.create"><?php echo e("Create Links") ?></option>
					    			<option value="links.edit"><?php echo e("Edit Links") ?></option>
					    			<option value="links.delete"><?php echo e("Delete Links") ?></option>				    				
				    			</optgroup>
				    			<?php if ($this->permission("splash") !== FALSE): ?>
				    				<optgroup label="<?php echo e("Splash page") ?>">
						    			<option value="splash.create"><?php echo e("Create Splash") ?></option>
						    			<option value="splash.edit"><?php echo e("Edit Splash") ?></option>
						    			<option value="splash.delete"><?php echo e("Delete Splash") ?></option>				    				
					    			</optgroup>
				    			<?php endif ?>
				    			<?php if ($this->permission("overlay") !== FALSE): ?>
				    				<optgroup label="<?php echo e("Overlay Page") ?>">
						    			<option value="overlay.create"><?php echo e("Create Overlay") ?></option>
						    			<option value="overlay.edit"><?php echo e("Edit Overlay") ?></option>
						    			<option value="overlay.delete"><?php echo e("Delete Overlay") ?></option>				    				
					    			</optgroup>
				    			<?php endif ?>	
									<?php if ($this->permission("pixels") !== FALSE): ?>
										<optgroup label="<?php echo e("Pixels") ?>">
						    			<option value="pixels.create"><?php echo e("Create Pixels") ?></option>
						    			<option value="pixels.edit"><?php echo e("Edit Pixels") ?></option>
						    			<option value="pixels.delete"><?php echo e("Delete Pixels") ?></option>				    				
					    			</optgroup>
				    			<?php endif ?>
									<?php if ($this->permission("domain") !== FALSE): ?>
										<optgroup label="<?php echo e("Domain") ?>">
						    			<option value="domain.create"><?php echo e("Add Custom Domain") ?></option>
						    			<option value="domain.delete"><?php echo e("Delete Custom Domain") ?></option>				    				
					    			</optgroup>
				    			<?php endif ?>				
									<?php if ($this->permission("bundle") !== FALSE): ?>
										<optgroup label="<?php echo e("Bundles") ?>">
						    			<option value="bundle.create"><?php echo e("Create Bundles") ?></option>
						    			<option value="bundle.edit"><?php echo e("Edit Bundles") ?></option>
						    			<option value="bundle.delete"><?php echo e("Delete Bundles") ?></option>				    				
					    			</optgroup>
				    			<?php endif ?>	
									<?php if ($this->permission("api") !== FALSE): ?>
				    			<option value="api.create"><?php echo e("Developer API") ?></option>		    				
				    			<?php endif ?>	
									<?php if ($this->permission("export") !== FALSE): ?>
				    			<option value="export.create"><?php echo e("Export Data") ?></option>		    				
				    			<?php endif ?>						    						    						    					    							    					    			
				    		</select>			
							</div>
						</div>
					</div>
					<?php echo Main::csrf_token(TRUE) ?>
					<button class="btn btn-primary" type="submit"><?php echo e("Invite") ?></button>
				</form>
			</div>			
		<?php endif ?>

		<div class="panel panel-default panel-body">
			<h3><?php echo e("Members") ?></h3>
			<div class="table-responsive">
				<table class="table">
					<tbody>
						<?php foreach ($team as $member): ?>
							<tr>
								<td><img src="<?php echo $this->avatar($member, 30) ?>" alt="" class="round"></td>
								<td><?php echo $member->name ? $member->name : $member->username ?>&nbsp;&nbsp;&nbsp;<?php echo ($member->active ? '<span class="label label-success">'.e("Active").'</span>' : '<span class="label label-danger">'.e("Inactive").'</span>') ?></td>
								<td><?php echo $member->email ?></td>
								<td>
									<ul>
										<?php if ($permissions = json_decode($member->teampermission)): ?>
											<?php foreach ($permissions as $permission): ?>
												<li><?php echo $permission ?></li>										
											<?php endforeach ?>											
										<?php endif ?>
									</ul>
								</td>
								<td>
									<?php if (!$this->isTeam()): ?>
										<a href="<?php echo Main::href("user/teams/edit?user={$member->id}") ?>" class="btn btn-xs btn-success"><?php echo e("Edit") ?></a>
										<a href="<?php echo Main::href("user/teams/remove".Main::nonce("delete_team-{$member->id}")."&user={$member->id}") ?>" class="btn btn-xs btn-danger delete"><?php echo e("Remove") ?></a>
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
  	<div class="panel panel-default panel-body">
			<h3><?php echo e("Events Permission") ?></h3>
			<p><?php echo e("Create: A create event will allow your team member to shorten links, create splash pages & overlay and bundles.") ?></p>
			<p><?php echo e("Edit: An edit event will allow your team member to edit links, splash pages & overlay and bundles.") ?></p>
			<p><?php echo e("Delete: A delete event will allow your team member to delete links, splash pages & overlay and bundles.") ?></p>
		</div>
  </div><!--/#widgets-->
</div><!--/.row-->