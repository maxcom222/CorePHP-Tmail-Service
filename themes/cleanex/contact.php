<?php defined("APP") or die() ?>
<section class="dark">
	<div class="container">
		<ol class="breadcrumb">
		  <li><a href="<?php echo Main::href("") ?>"><?php echo e("Home") ?></a></li>
		  <li class="active"><?php echo e("Contact us") ?></li>
		</ol>
	</div>
</section>
<section>
	<div class="container">    
		<div class="centered form">      
      <?php echo Main::message() ?>
      <form role="form" class="live_form" method="post" action="<?php echo Main::href("contact")?>">
      	<h3><?php echo e("Contact us") ?></h3>
      	<p><?php echo e("If you have any questions, feel free to contact us on this page."); ?></p>
      	<hr>
        <div class="form-group">
          <label><?php echo e("Name") ?></label>
          <input type="text" class="form-control" name="name" value="<?php ($this->logged() ? $this->user->name : "") ?>">	            
        </div>
        <div class="form-group">
          <label><?php echo e("Email") ?> (<?php echo e("Required") ?>)</label>
          <input type="email" class="form-control" name="email" value="<?php ($this->logged() ? $this->user->email : "") ?>" required>		            
        </div>  
        <div class="form-group">
          <label><?php echo e("Message") ?> (<?php echo e("Required") ?>)</label>
          <textarea name="message" class="form-control" rows="10" required></textarea>	            
        </div>          
				<p><?php echo Main::captcha() ?></p>
        <?php echo Main::csrf_token(TRUE) ?>
        <button type="submit" class="btn btn-primary"><?php echo e("Send") ?></button>       
      </form>        
		</div>
	</div>
</section>