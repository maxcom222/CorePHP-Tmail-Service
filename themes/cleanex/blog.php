<?php defined("APP") or die() ?>
<section class="dark">
	<div class="container">
		<ol class="breadcrumb">
		  <li><a href="<?php echo Main::href("") ?>"><?php echo e("Home") ?></a></li>
	  	<li class="active"><?php echo e("Blog") ?></li>
		</ol>
	</div>
</section>
<section>
	<div class="container content">
		<?php echo $this->ads(728,0) ?>		
		<div class="row main-content">
			<div class="col-md-8">				
				<?php if (!$posts): ?>

				<div class="panel panel-body panel-default">
					<?php echo e("No posts found.") ?>
				</div>						
				<?php endif ?>
				
				<?php foreach ($posts as $post): ?>
				<div class="panel panel-body panel-default">
					<h3><a href="<?php echo Main::href("blog/{$post->slug}") ?>"><?php echo $post->title ?></a></h3>
					<span><?php echo date("d.M.Y", strtotime($post->date)) ?></span>
					<hr>						
					<?php echo Main::readmore($post->content, Main::href("blog/{$post->slug}")) ?>
				</div>					
				<?php endforeach ?>					
				
				<?php if($max > 1): ?>
					<div class="panel panel-body panel-default">
						<?php echo $pagination ?>
					</div>
				<?php endif ?>
			</div>
			<div class="col-md-4">
				<?php echo $this->ads(300,0) ?>
				<?php echo $this->widgets("social_count") ?>
				<?php echo $this->widgets("top_posts") ?>
			</div>
		</div>		
	</div>
</section>