<?php if(!defined('APP')) die(); ?>
<section>
	<div class="container">
		<div class="custom-splash panel panel-default" id="splash">
			<div class="banner"><a href="<?php echo $data->product ?>" rel="nofollow" target="_blank"><img src="<?php echo $data->banner ?>"></a></div><!-- /.banner -->
			<div class="custom-message">
				<div class="c-avatar"><img src="<?php echo $data->avatar ?>"></div><!-- /.avatar -->
				<div class="c-message">
					<h2><?php echo $data->title ?></h2>
					<?php echo $data->message ?>
					<p><a href="<?php echo $data->product ?>" rel="nofollow" target="_blank" class="btn btn-primary btn-xs"><?php echo e("View site") ?></a></p>
				</div><!-- /.messsage -->
				<div class="c-countdown"><span><?php echo $this->config["timer"] ?></span><?php echo e("seconds") ?></div><!-- /.c-countdown -->
			</div><!-- /.custom-message -->
		</div><!-- /.custom-splash -->	
	</div><!-- /.container -->
</section>