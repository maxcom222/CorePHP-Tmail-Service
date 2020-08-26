<?php defined("APP") or die() ?>
<section>
	<div class="container">    
		<div class="centered form">
      
      <div class="site_logo">
        <?php if (!empty($this->config["logo"])): ?>
          <a href="<?php echo $this->config["url"] ?>"><img src="<?php echo $this->config["url"] ?>/content/<?php echo $this->config["logo"] ?>" alt="<?php echo $this->config["title"] ?>"></a>
        <?php else: ?>
          <h3><a href="<?php echo $this->config["url"] ?>"><?php echo $this->config["title"] ?></a></h3>
        <?php endif ?>
      </div> 

      <?php echo Main::message() ?>
      <form role="form" class="live_form" method="post" action="<?php echo Main::href("user/forgot/{$this->id}")?>">
        <div class="form-group">
          <label for="pass1"><?php echo e("Password")?></label>
          <input type="password" class="form-control" id="pass1" placeholder="Password" name="password">             
        </div>        
        <div class="form-group">
          <label for="pass2"><?php echo e("Confirm Password")?></label>
          <input type="password" class="form-control" id="pass2" placeholder="Confirm Password" name="cpassword">               
        </div>
        <?php echo Main::csrf_token(TRUE) ?>
        <button type="submit" class="btn btn-primary"><?php echo e("Reset Password")?></button>        
      </form>        
		</div>
	</div>
</section>