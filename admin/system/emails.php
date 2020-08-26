<?php if(!defined("APP")) die(); // Protect this page ?>
<div class="row">
	<div class="col-md-9">
		<div class="row">
			<div class="col-md-6">
				<div class="panel">
					<div class="panel-heading"><label for="email.registration">Registration Email</label></div>
					<div class="panel-body">
						<form action="<?php echo Main::ahref("emails") ?>" method="post">
							<div class="form-group">
								<textarea name="email.registration" id="email.registration" cols="30" rows="10" class="form-control editor"><?php echo $this->config["email.registration"] ?></textarea>
							</div>
							<?php echo Main::csrf_token(TRUE) ?>
							<button type="submit" class="btn btn-primary">Save</button>
						</form>				
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel">
					<div class="panel-heading"><label for="email.activation">Activation Email</label></div>
					<div class="panel-body">
						<form action="<?php echo Main::ahref("emails") ?>" method="post">
							<div class="form-group">
								<textarea name="email.activation" id="email.activation" cols="30" rows="10" class="form-control editor"><?php echo $this->config["email.activation"] ?></textarea>
							</div>
							<?php echo Main::csrf_token(TRUE) ?>
							<button type="submit" class="btn btn-primary">Save</button>
						</form>				
					</div>
				</div>
			</div>			
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="panel">
					<div class="panel-heading"><label for="email.activated">Activation Success Email</label></div>
					<div class="panel-body">
						<form action="<?php echo Main::ahref("emails") ?>" method="post">
							<div class="form-group">
								<textarea name="email.activated" id="email.activated" cols="30" rows="10" class="form-control editor"><?php echo $this->config["email.activated"] ?></textarea>
							</div>
							<?php echo Main::csrf_token(TRUE) ?>
							<button type="submit" class="btn btn-primary">Save</button>
						</form>				
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel">
					<div class="panel-heading"><label for="email.reset">Password Reset Email</label></div>
					<div class="panel-body">
						<form action="<?php echo Main::ahref("emails") ?>" method="post">
							<div class="form-group">
								<textarea name="email.reset" id="email.reset" cols="30" rows="10" class="form-control editor"><?php echo $this->config["email.reset"] ?></textarea>
							</div>
							<?php echo Main::csrf_token(TRUE) ?>
							<button type="submit" class="btn btn-primary">Save</button>
						</form>				
					</div>
				</div>
			</div>			
		</div>			
		<div class="row">
			<div class="col-md-6">
				<div class="panel">
					<div class="panel-heading"><label for="email.invitation">Team Invitation Email</label></div>
					<div class="panel-body">
						<form action="<?php echo Main::ahref("emails") ?>" method="post">
							<div class="form-group">
								<textarea name="email.invitation" id="email.invitation" cols="30" rows="10" class="form-control editor"><?php echo $this->config["email.invitation"] ?></textarea>
							</div>
							<?php echo Main::csrf_token(TRUE) ?>
							<button type="submit" class="btn btn-primary">Save</button>
						</form>				
					</div>
				</div>
			</div>
		</div>				
	</div>
	<div class="col-md-3">
		<div class="panel">
			<div class="panel-heading">Shortcodes</div>
			<div class="panel-body">
        <ul>
          <li>User's Username: <strong>{user.username}</strong></li>
          <li>User's Email: <strong>{user.email}</strong></li>
          <li>User's Sign Up Date: <strong>{user.date}</strong></li>
          <li>Activation Link or Password Reset: <strong>{user.activation}</strong></li>
          <li>Site's Title: <strong>{site.title}</strong></li>
          <li>Site's Link: <strong>{site.link}</strong></li>
        </ul>				
			</div>
		</div>
	</div>
</div>