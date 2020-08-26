<?php defined("APP") or die() // Main User Page Template (used to add dynamic pages) ?>
<?php echo $before ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
		<?php echo Main::message() ?>  	
		<div class="main-content panel panel-default panel-body">
			<h3><?php echo $header ?></h3>
			<?php echo $content ?>
		</div>	
  </div><!--/#user-content-->
  <div id="widgets" class="col-md-4">
  	<?php echo $this->sidebar() ?>
		<?php echo $widgets ?>
  </div><!--/#widgets-->
</div><!--/.row-->