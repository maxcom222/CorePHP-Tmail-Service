<?php defined("APP") or die() // Stats Page ?>
<section>
	<div class="container stats-page">
		<div class="panel panel-body panel-default">
			<div class="row info">
				<div class="col-md-3 thumb">
					<img src="<?php echo $url->short ?>/i" alt="">
				</div>
				<hr class="visible-sm visible-xs">
				<div class="col-md-9 url-info">
					<h2>
						<a href="<?php echo $url->url ?>" rel="nofollow"><?php echo fixTitle($url->meta_title) ?></a>
						<small class="pull-right"><?php echo Main::timeago($url->date) ?></small>
						<span><?php echo $url->meta_description ?></span>
					</h2>
					<hr class="visible-sm visible-xs">
					<div class="row">
						<div class="col-sm-4 url-stats">
								<?php echo $url->click ?>
								<span><?php echo e("Clicks") ?></span>
							<br>
						</div>
						<div class="div col-sm-4 url-stats">							
								<?php echo $url->unique ?>
								<span><?php echo e("Unique Clicks")?></span>
						</div>
						<div class="col-sm-4 text-center">
							<p>
								<i class="glyphicon glyphicon-link"></i> <?php echo (strlen($url->fullurl) > 25 ? Main::truncate($url->fullurl, 25) : $url->fullurl) ?> <a href="#copy" class="copy inline-copy hidden-xs"  data-clipboard-text="<?php echo $url->fullurl ?>"><?php echo e("Copy")?></a>								
							</p>
							<p>
								<i class="glyphicon glyphicon-qrcode"></i> <?php echo (strlen($url->fullurl) > 25 ? Main::truncate($url->fullurl, 25) : $url->fullurl) ?>/qr <a href="#copy" class="copy inline-copy hidden-xs"  data-clipboard-text="<?php echo $url->fullurl ?>/qr"><?php echo e("Copy")?></a>								
							</p>	
								<div class="btn-group btn-group-sm">
								<a href="https://www.facebook.com/sharer.php?u=<?php echo$url->fullurl ?>" class="btn btn-facebook u_share"><?php echo e("Share on") ?> Facebook</a>
	            	<a href="https://twitter.com/share?url=<?php echo$url->fullurl ?>&amp;text=Check+out+this+url" class="btn btn-twitter u_share"><?php echo e("Share on") ?> Twitter</a>								
							</div>
						</div>						
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php echo $this->ads(728) ?>
<section class="analytics">
	<div class="container">
		<div class="panel">
			<div class="panel-heading">
				<div class="btn-group btn-group-xs pull-right">
					<a href="#" class="btn btn-primary chart_data active" data-value='d'><?php echo e("Daily") ?></a>			
					<a href="#" class="btn btn-primary chart_data" data-value='<?php echo json_encode(array("m",$url->alias.$url->custom,$url->click)) ?>'><?php echo e("Monthly") ?></a>
					<a href="#" class="btn btn-primary chart_data" data-value='<?php echo json_encode(array("y",$url->alias.$url->custom,$url->click)) ?>'><?php echo e("Yearly") ?></a>   				
				</div>
			</div>
			<div class="panel-body">
				<div id="url-chart" class="chart"></div>
			</div>
		</div>
	</div>
</section>
<section class="analytics dark">
	<div class="container">
		<div class="row">
      <div class="col-md-6">
      	<div id="country" style="width:100%; height: 300px"></div>
      </div>
    	<div class="col-md-6">	
    		<h3><?php echo e("Top Countries") ?></h3>
    		<ol id="country-list"></ol>
    	</div> 			    
		</div>	
	</div>	
</section>
<section class="analytics">
	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default panel-body panel-alt">
					<h3><span><?php echo e("Operating Systems")?></span></h3>
					<canvas style="width:99%;height:300px;" id="os"></canvas>
				</div>	
			</div>
			<div class="col-md-6">
				<div class="panel panel-default panel-body panel-alt">
					<h3><span><?php echo e("Browsers")?></span></h3>
					<canvas style="width:99%;height:300px;" id="browsers"></canvas>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="analytics">
	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default panel-body">
					<h3><?php echo e("Referrers")?></h3>
					<ul id="referrer">      
			  	</ul>	
				</div>	
			</div>
			<div class="col-md-6">
				<div class="panel panel-default panel-body">
					<h3><?php echo e("Social Shares")?></h3>
					<div style="width:99%;height:300px;" id="social-shares"></div>
				</div>
			</div>
		</div>
	</div>
</section>