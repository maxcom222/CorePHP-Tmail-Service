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
      <form role="form" class="live_form" id="login_form" method="post" action="<?php echo Main::href("user/register")?>">

        <?php if(!$this->config["private"] && $this->config["user"] &&  ($this->config["fb_connect"] || $this->config["tw_connect"] || $this->config["gl_connect"])):?>
          <div class="social">
            <h3><?php echo e("Login using a social network") ?></h3>
            <?php if($this->config["fb_connect"]):?>
            <a href="<?php echo $this->config["url"]?>/user/login/facebook" class="btn btn-facebook btn-inline"><i class="fab fa-facebook-f"></i></a>
            <?php endif;?>
            <?php if($this->config["tw_connect"]):?>
            <a href="<?php echo $this->config["url"]?>/user/login/twitter" class="btn btn-twitter btn-inline"><i class="fab fa-twitter"></i></a>
            <?php endif;?>
            <?php if($this->config["gl_connect"]):?>
            <a href="<?php echo $this->config["url"]?>/user/login/google" class="btn btn-google btn-inline"><i class="fab fa-google"></i></a>
            <?php endif;?>            
          </div>
        <?php endif;?>    

        <div class="form-group">
          <label for="name"><?php echo e("Username") ?></label>
          <input type="text" class="form-control" id="name" placeholder="<?php echo e("Please enter a username") ?>" name="username">
        </div>        
        <div class="form-group">
          <label for="email"><?php echo e("Email address")?></label>
          <input type="email" class="form-control" id="email" placeholder="<?php echo e("Please enter a valid email.") ?>" name="email">
        </div>
        <div class="form-group">
          <label for="pass"><?php echo e("Password")?></label>
          <input type="password" class="form-control" id="pass" placeholder="<?php echo e("Please enter a valid password.") ?>" name="password">
        </div>     
        <div class="form-group">
          <label for="pass2"><?php echo e("Confirm Password")?></label>
          <input type="password" class="form-control" id="pass2" placeholder="<?php echo e("Please confirm your password.") ?>" name="cpassword">
        </div>  
        <?php echo Main::captcha() ?>         
        <div class="form-group">
          <label>
              <input type="checkbox" name="terms" value="1" data-class="blue">  
              <span class="check-box"><?php echo e("I agree to the")?> <a href="<?php echo $this->config["url"] ?>/page/terms" target="_blank"><?php echo e("terms and conditions")?></a>.</span>
          </label>
        </div>          
        <?php echo Main::csrf_token(TRUE) ?>
        <button type="submit" class="btn btn-primary"><?php echo e("Create account")?></button>
      </form>        
		</div>
	</div>
</section>