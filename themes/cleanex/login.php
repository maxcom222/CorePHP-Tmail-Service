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
      <form role="form" class="live_form form" id="login_form" method="post" action="<?php echo Main::href("user/login")?>"> 
          
        <?php if(!$this->config["private"] && !$this->config["maintenance"] && $this->config["user"] && ($this->config["fb_connect"] || $this->config["tw_connect"] || $this->config["gl_connect"])):?>
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
          <label for="email"><?php echo e("Email or username") ?>  
            <?php if($this->config["user"] && !$this->config["private"] && !$this->config["maintenance"]): ?>
              <a href="<?php echo Main::href("user/register")?>" class="pull-right">(<?php echo e("Create account")?>)</a>
            <?php endif ?>
          </label>
          <input type="text" class="form-control" id="email" placeholder="Enter email" name="email">
        </div>
        <div class="form-group">
          <label for="pass"><?php echo e("Password")?> <a href="#forgot" class="pull-right" id="forgot-password">(<?php echo e("Forgot Password")?>)</a></label>
          <input type="password" class="form-control" id="pass" placeholder="Password" name="password">
        </div>
        <p><?php echo Main::captcha() ?></p>
        <div class="form-group">
          <label>
              <input type="checkbox" name="rememberme" value="1" data-class="blue">  
              <span class="check-box"><?php echo e("Remember me")?></span>
          </label>
        </div>
        <?php echo Main::csrf_token(TRUE) ?>
        <button type="submit" class="btn btn-primary"><?php echo e("Login")?></button>
      </form>  

      <form role="form" class="live_form" id="forgot_form" method="post" action="<?php echo Main::href("user/forgot")?>">
        <div class="form-group">
          <label for="email1"><?php echo e("Email address")?></label>
          <input type="email" class="form-control" id="email1" placeholder="Enter email" name="email">
        </div>                 
        <p><?php echo Main::captcha() ?></p>
        <?php echo Main::csrf_token(TRUE) ?>
        <button type="submit" class="btn btn-primary"><?php echo e("Reset Password")?></button>
        <a href="<?php echo Main::href("user/login") ?>" class="pull-right">(<?php echo e("Back to login")?>)</a>
      </form>        
		</div>
	</div>
</section>