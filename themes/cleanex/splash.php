<?php defined("APP") or die() // Media Page ?>
<section>
	<div class="container splash">
		<?php echo $this->ads(728,FALSE) ?>
		<div class="panel panel-default panel-body">
			<div class="row">
				<div class="col-md-4 thumb">
					<img src="<?php echo $url->short ?>/i">
				</div>
				<div class="col-md-8">
						<h2>
							<?php if (!empty($url->meta_title)): ?>
								<?php echo fixTitle($url->meta_title) ?>
							<?php else: ?>
								<?php echo e("You are about to be redirected to another page.") ?>
							<?php endif ?>
						</h2>
						<p class="description">
							<?php if (!empty($url->meta_description)): ?>
								<?php echo $url->meta_description ?>
							<?php endif ?>
						</p>
						<br>
						<div class="row">
							<div class="col-sm-6">
								<a href="<?php echo $url->url ?>" class="btn btn-secondary btn-block redirect" rel="nofollow"><?php echo e("Redirect me"); ?></a>
							</div>
							<div class="col-sm-6">
								<a href="<?php echo $this->config["url"] ?>" class="btn btn-primary btn-block" rel="nofollow"><?php echo e("Take me to your homepage") ?></a></a>
							</div>
						</div>
						<hr>
						<p class="disclaimer">
							<?php echo e("You are about to be redirected to another page. We are not responisible for the content of that page or the consequences it may have on you.") ?>
						</p>
				</div>
			</div>
		</div>
	</div>
</section>