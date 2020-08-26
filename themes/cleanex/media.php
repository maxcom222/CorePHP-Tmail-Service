<?php defined("APP") or die() // Media Page ?>
<section>
	<div class="container media">
		<div class="row">
			<div class="col-md-8">
				<div class="panel panel-default panel-body">
					<div class="embed">
						<?php echo $url->embed ?>
					</div>
					<div class="row info">
						<div class="col-sm-9">
							<h3><?php echo fixTitle($url->meta_title) ?></h3>
						</div>
						<div class="col-sm-3 text-right">
							<span><?php echo $url->click+1 ?></span>
							<?php echo e("Views") ?>
						</div>						
					</div>
					<p class="description">
						<?php echo $url->meta_description ?>				
					</p>					
				</div>
				<?php echo $this->ads(728) ?>
				<?php echo $this->comment() // Uses facebook system. Add your appid to activate it ?>
			</div>
			<div class="col-md-4">
				<?php echo $this->ads(300) ?>	
				<div class="panel panel-default panel-body">
					<h3><?php echo e("Short URL") ?></h3>
					<input type="text" class="form-control" value="<?php echo $url->shorturl ?>">
					<br>
					<a href="#copy" class="btn btn-primary copy" data-clipboard-text="<?php echo $url->shorturl ?>"><?php echo e("Copy") ?></a>
					<?php if($this->config["sharing"]): ?>
						<hr>
						<p>
	          	<a href="https://www.facebook.com/sharer.php?u=<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"]) ?>/<?php echo $url->alias.$url->custom ?>" class="btn btn-facebook btn-block u_share"><?php echo e("Share on") ?> Facebook</a>
	            <a href="https://twitter.com/share?url=<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"]) ?>/<?php echo $url->alias.$url->custom ?>&amp;text=Check+out+this+url" class="btn btn-twitter btn-block u_share"><?php echo e("Share on") ?> Twitter</a>
						</p>
					<?php endif ?>					
				</div>
			</div>				
		</div>
	</div>
</section>