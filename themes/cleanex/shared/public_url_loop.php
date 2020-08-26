<?php defined("APP") or die() // This file is looped in each instances to show the URL. Please don't edit this fiel if you don't know what you are doing! ?>
<div class="url-list fix" id="url-container-<?php echo $url->id ?>" data-id="<?php echo $url->id ?>">
	<div class="row">
		<div class="col-sm-10 url-info">
			<h3 class="title">
				<img src="<?php echo Main::href("{$url->alias}{$url->custom}/ico") ?>" alt="Favicon">
				<a href="<?php echo $url->domain ?>/<?php echo $url->alias.$url->custom ?>" target="_blank"><?php echo Main::truncate(empty($url->meta_title)?$url->url:fixTitle($url->meta_title),50) ?></a>
			</h3>
			<p class="description"><?php echo $url->meta_description ?></p>
			<div class="short-url">
				<a href="<?php echo $url->domain ?>/<?php echo $url->alias.$url->custom ?>" target="_blank"><?php echo $url->domain ?>/<?php echo $url->alias.$url->custom ?></a>
				<a href="#copy" class="copy inline-copy" data-value="<?php echo $url->domain ?>/<?php echo $url->alias.$url->custom ?>"><?php echo e("Copy")?></a>							
				&nbsp;&nbsp;&bullet;&nbsp;&nbsp;<i class='glyphicon glyphicon-time'></i> <?php echo Main::timeago($url->date) ?>
			</div>
		</div>
		<div class="col-sm-2 url-stats">
			<strong><?php echo $url->click ?></strong>
			<a href="<?php echo $url->domain ?>/<?php echo $url->alias.$url->custom ?>+" target="_blank" class="btn btn-primary btn-xs"><?php echo e("Clicks") ?></a>
		</div>
	</div>
</div><!-- /.url-list -->