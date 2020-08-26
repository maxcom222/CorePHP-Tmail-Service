<?php defined("APP") or die() // Main Page ?>  
<section class="hero">
  <div class="container">
    <div class="row">
      <div class="col-md-10 col-sm-12">
        <div class="promo">
          <h1><?php echo $this->config["title"] ?></h1>
          <p class="description"><?php echo $this->config["description"] ?></p>
        </div>        
        <?php echo Main::message() ?>
        <?php echo $this->shortener(array("multiple" => FALSE)) ?>
      </div>
    </div>
  </div>   
</section>
<?php if(!$this->history()): ?>
  <section id="mainto">
    <div class="container">
      <h3 class="text-center featureH">
        <?php echo e("One short link, infinite possibilities.") ?>        
      </h3>
      <p class="text-center featureP">
        <?php echo e("A short link is a powerful marketing tool when you use it carefully. It is not just a link but a medium between your customer and their destination. A short link allows you to collect so much data about your customers and their behaviors.") ?>
      </p>
      <div class="row feature">
        <div class="col-sm-7 image">
          <div class="row">
            <div class="col-md-6">
              <div class="panelette">
                <h3>
                  <i class="glyphicon glyphicon-screenshot"></i>
                  <?php echo e("Target. Re-target.") ?>
                </h3>
                <p>
                  <?php echo e("Target your customers to increase your reach and redirect them to a relevant page. Add a pixel to retarget them in your social media ad campaign to capture them.") ?>
                </p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="panelette panelette-grad">
                <h3>
                  <i class="glyphicon glyphicon-fire"></i>
                  <?php echo e("Measure. Optimize.") ?>
                </h3>
                <p>
                  <?php echo e("Share your links to your network and measure data to optimize your marketing campaign's performance. Reach an audience that fits your needs.") ?>
                </p>                
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-5 info">
          <h2>
            <i class="glyphicon glyphicon-send"></i>
            <small><?php echo e("Reach & increase sales.") ?></small>
            <?php echo e("Perfect for sales & marketing") ?>
          </h2>
          <p>
            <?php echo e("Understanding your users and customers will help you increase your conversion. Our system allows you to track everything. Whether it is the amount of clicks, the country or the referrer, the data is there for you to analyze it.") ?>
          </p>
        </div>      
      </div>   
      <p class="text-center">
        <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-lg btn-round"><?php echo e("Create an account") ?></a></p>
      </p>              
      <p class="text-center"><span class="small"><?php echo e("Start for free, upgrade later - No credit card required.") ?></span></p>                  
    </div>    
  </section>
  <section class="red">
    <div class="container">
      <div class="row feature">
        <div class="col-sm-5 info">
          <h2>
            <i class="glyphicon glyphicon-filter"></i>
            <small><?php echo e("Target interested users.") ?></small>
            <?php echo e("Powerful tools that work") ?>
          </h2>
          <p>
            <?php echo e("Our product lets your target your users to better understand their behavior and provide them a better overall experience through smart re-targeting. We provide you many powerful tools to reach them better.") ?>
          </p>
          <br>         
        </div>
        <div class="col-sm-7 image">
          <img src="<?php echo $this->config["url"] ?>/themes/cleanex/assets/images/landing.png" alt="<?php echo e("Powerful tools that work") ?>">
        </div>
      </div>         
    </div>    
  </section>
  <section>
    <div class="container">
      <div class="feature">
        <div class="row">
          <div class="col-sm-7 rand image">
            <div class="rand1"> <i class="glyphicon glyphicon-link"></i> <h3><?php echo e("Link Controls") ?></h3></div>
            <div class="rand2"> <i class="glyphicon glyphicon-lock"></i> <h3><?php echo e("Privacy Control") ?></h3></div>
            <div class="rand3"> <i class="glyphicon glyphicon-briefcase"></i> <h3><?php echo e("Link Management") ?></h3></div>
            <div class="rand4"> <i class="glyphicon glyphicon-dashboard"></i> <h3><?php echo e("Powerful Dashboard") ?></h3></div>
            <div class="rand5"> <i class="glyphicon glyphicon-star"></i> <h3><?php echo e("Premium Features") ?></h3></div>
            <div class="rand6"> <i class="glyphicon glyphicon-stats"></i> <h3><?php echo e("Statistics") ?></h3></div>
          </div>
          <div class="col-sm-5 info">
            <h2>
              <i class="glyphicon glyphicon-tasks"></i>
              <small><?php echo e("Control on each and everything.") ?></small>
              <?php echo e("Complete control on your links") ?>
            </h2>
            <p>
              <?php echo e("With our premium membership, you will have complete control on your links. This means you can change the destination anytime you want. Add, change or remove any filters, anytime.") ?>
            </p>
          </div>      
        </div>   
      </div>   
    </div>
  </section>  
  <section class="light">
    <h3 class="text-center featureH"><?php echo e("Targeting your customers") ?></h3>
    <div class="container">
      <div class="featurette">
        <div class="row">
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-globe"></i>
            <h3><?php echo e("Target Customers") ?></h3>
            <p><?php echo e("Target your users based on their location and device and redirect them to specialized pages to increase your conversion.") ?></p>
          </div>    
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-star"></i>
            <h3><?php echo e("Custom Landing Page") ?></h3>
            <p><?php echo e("Create a custom landing page to promote your product or service on forefront and engage the user in your marketing campaign.") ?></p>
          </div>      
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-asterisk"></i>
            <h3><?php echo e("Overlays") ?></h3>
            <p><?php echo e("Use our overlay tool to display unobtrusive notifications on the target website. A perfect way to send a message to your customers or run a promotion campaign.") ?></p>
          </div>
        </div>    
        <br> 
        <div class="row">
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-th"></i>
            <h3><?php echo e("Event Tracking") ?></h3>
            <p><?php echo e("Add your custom pixel from providers such as Facebook and track events right when they are happening.") ?></p>
          </div>        
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-glass"></i>
            <h3><?php echo e("Premium Aliases") ?></h3>
            <p><?php echo e("As a premium membership, you will be able to choose a premium alias for your links from our list of reserved aliases.") ?></p>
          </div>    
          <div class="col-sm-4">
            <i class="glyphicon glyphicon-cloud"></i>
            <h3><?php echo e("Robust API") ?></h3>
            <p><?php echo e("Use our powerful API to build custom applications or extend your own application with our powerful tools.") ?></p>
          </div>         
        </div>  
      </div>
    </div>
    <p class="text-center">
      <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-lg btn-round"><?php echo e("Get started for free") ?></a>
    </p>         
  </section>
<?php endif; ?>
<?php $this->public_list() ?>
<section class="calltoaction">
  <div class="container">
    <div class="actionbar">
      <h2><?php echo e("Start your marketing campaign now and reach your customers efficiently.") ?></h2>
      <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-round"><?php echo e("Register now") ?></a>
    </div>
    <?php if ($this->config["homepage_stats"]): ?>
      <div class="stats">
        <h2 class="text-center"><?php echo e("Marketing with confidence.") ?></h2>
        <div class="row">
          <div class="col-xs-4">
            <strong><?php echo e("Powering") ?></strong>      
            <h3><?php echo $this->count("urls") ?> <span><?php echo e("Links") ?></span></h3>
          </div>
          <div class="col-xs-4">
            <strong><?php echo e("Serving") ?></strong>      
            <h3><?php echo $this->count("clicks") ?> <span> <?php echo e("Clicks") ?></span></h3>
          </div>
          <div class="col-xs-4">
            <strong><?php echo e("Trusted by") ?></strong>
            <h3><?php echo $this->count("users") ?> <span><?php echo e("Customers") ?></span></h3>
          </div>
        </div>           
      </div>
    <?php endif ?> 
  </div>
</section>