<?php defined("APP") or die() ?>
<!DOCTYPE html>
<html lang="en" prefix="og: http://ogp.me/ns#">
  <head>       
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0" />  
    <meta name="description" content="<?php echo Main::description() ?>" />
    <!-- Open Graph Tags -->
    <?php echo Main::ogp(); ?> 

    <title><?php echo Main::title() ?></title> 
        
    <!-- Bootstrap core CSS -->
    <link href="<?php echo $this->config["url"] ?>/static/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body{
        background: transparent;
        position: relative;
      }
      #frame{
        background: #151720;
        background-image: -moz-linear-gradient(45deg, #544C98 0%, #151720 100%);
        background-image: -webkit-linear-gradient(45deg, #544C98 0%, #151720 100%);
        background-image: -ms-linear-gradient(45deg, #544C98 0%, #151720 100%);           
        color: #fff;
        min-height: 62px;
        position: absolute;
        width: 100%;
        box-shadow: 0 5px 5px rgba(0,0,0,0.1);   
        z-index: 9999;     
      }
      .site-logo a{
        color: #fff;
        text-decoration: none;
        font-weight: 700;
      }
      .site-logo{
        font-size: 18px;
        padding-left: 10px;
      }
      .btn-group{
        margin: 15px 15px 0 0
      }
      #site{
        position: absolute;
        width: 100%;
        top: 62px;
        z-index: 1;
      }
    </style>
    <!-- Required Javascript Files -->
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jquery.min.js?v=1.11.0"></script>
    <script type="text/javascript">
      var appurl="<?php echo $this->config["url"] ?>";
      var token="<?php echo $this->config["public_token"] ?>";
    </script>
    <?php Main::enqueue() // Add scripts when needed ?>    
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body<?php echo Main::body_class() ?> id="body" style="overflow:hidden">
    <section>
      <div id="frame">
        <div class="row">
          <div class="col-sm-4">
            <h1 class="site-logo"><a href="<?php echo $this->config["url"] ?>"><?php echo $this->config["title"] ?></a></h1>
          </div>
          <div class="col-sm-4 hidden-xs">
            <?php echo $this->ads("frame",FALSE) ?>
          </div>
          <div class="col-sm-4">
            <div class="btn-group btn-group-sm pull-right">
              <a href="https://www.facebook.com/sharer.php?u=<?php echo ($url->domain ? $url->domain : $this->config["url"]) ?>/<?php echo $url->alias.$url->custom ?>" class="btn btn-primary u_share" target="_blank"><?php echo e("Share") ?></a>
              <a href="https://twitter.com/share?url=<?php echo ($url->domain ? $url->domain : $this->config["url"]) ?>/<?php echo $url->alias.$url->custom ?>&amp;text=Check+out+this+url" class="btn btn-primary u_share" target="_blank"><?php echo e("Tweet") ?></a>
              <a href="<?php echo $url->url ?>" class="btn btn-primary"><?php echo e("Close") ?></a>
            </div>
          </div>         
        </div><!-- /.row -->
      </div><!-- /#frame -->
      <iframe id="site" src="<?php echo $url->url;?>" frameborder="0" style="border: 0; width: 100%; height: 100%" scrolling="yes"></iframe>
    </section>
    <script type="text/javascript">
       $("iframe#site").height($(document).height()-$("#frame").height());
    </script>
    <?php Main::enqueue('footer') ?>  
  </body>
</html>  