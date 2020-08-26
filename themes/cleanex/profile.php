<?php defined("APP") or die() // Public Profile ?>
<section>
	<?php echo $this->ads(728,0) ?>
	<div class="container content addmargin">
		<div class="row" id="user-content">
			<div class="col-md-8">
				<div class="panel panel-default panel-body return-ajax" id="data-container">
					<h3><?php echo $heading ?></h3>
						<?php foreach ($urls as $url): ?>
							<?php include(TEMPLATE."/shared/public_url_loop.php") ?>
						<?php endforeach ?>
						<?php echo $pagination ?>	
				</div>	
			</div>
			<div class="col-md-4">
				<div class="panel panel-dark panel-body text-center">					
					<div class="avatar-holder row">
						<img src="<?php echo $user->avatar ?>" alt="<?php echo $user->username ?>'s Avatar" class="avatar pull-left">
						<div class="pull-left">
							<strong><?php echo $user->username ?></strong>
							<span><?php echo e("Since") ?> <?php echo date("F, Y",strtotime($user->date)) ?></span>
						</div>
					</div>
					<div class="row clearfix">
						<div class="col-sm-6">
							<h2><?php echo $this->count("user_public_urls",$user->id) ?></h2>
								<strong><?php echo e("Public URLs") ?></strong>
						</div>
						<div class="col-sm-6">
							<h2><?php echo $this->count("user_public_bundles",$user->id) ?></h2>
							<strong><?php echo e("Public Bundles") ?></strong>
						</div>
					</div>
					<br>					
					<div class="btn-group btn-group-sm">
						<a href="<?php echo Main::href("profile/{$user->username}") ?>" class="btn btn-primary"><?php echo e("View Profile") ?></a>						
						<a href="#" class="btn btn-primary ajax_call" data-class="return-ajax" data-id="<?php echo base64_encode(Main::strrand(3).$user->id) ?>" data-active="active" data-action="bundles"><?php echo e("View Bundles") ?></a>										
					</div>
				</div>
				<?php echo $this->widgets("social_count") ?>
				<?php echo $this->ads(300,0) ?>
			</div>
		</div>
	</div>
</section>