<?php defined("APP") or die() ?>
<section class="dark">
	<div class="container">
		<ol class="breadcrumb">
		  <li><a href="<?php echo Main::href("") ?>"><?php echo e("Home") ?></a></li>
	  	<li><a href="<?php echo Main::href("blog") ?>"><?php echo e("Blog") ?></a></li>
	  	<li class="active"><?php echo $post->title ?></li>
		</ol>
	</div>
</section>
<section>
	<div class="container content">
		<?php echo $this->ads(728,0) ?>		
		<div class="row main-content">
			<div class="col-md-8">				
				<div class="panel panel-body panel-default">
					<h3><a href="<?php echo Main::href("blog/{$post->slug}") ?>"><?php echo $post->title ?></a></h3>
					<span><?php echo date("d.M.Y", strtotime($post->date)) ?></span>
					<hr>
					<?php echo $post->content ?>
					<div class="social-share"><?php echo Main::share(Main::href("blog/{$post->slug}"), $post->title) ?></div>
				</div>							
			</div>
			<div class="col-md-4">
				<?php echo $this->ads(300,0) ?>
				<?php echo $this->widgets("social_count") ?>
				<?php echo $this->widgets("top_posts") ?>
			</div>
		</div>		
	</div>
</section>