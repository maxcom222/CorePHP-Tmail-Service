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
    <meta name="keywords" content="<?php echo $this->config["keywords"] ?>" />
    <!-- Open Graph Tags -->
    <?php echo Main::ogp(); ?>

    <title><?php echo Main::title() ?></title>
    <link href="<?php echo $this->config["url"] ?>/static/ubold/css/custom_bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Bootstrap core CSS -->
<!--    <link href="<?php /*echo $this->config["url"] */?>/static/css/custom.css" rel="stylesheet">-->
    <link href="<?php echo $this->config["url"] ?>/static/css/bootstrap.min.css" rel="stylesheet">
    <!-- Component CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo $this->config["url"] ?>/themes/<?php echo $this->config["theme"] ?>/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->config["url"] ?>/static/css/components.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->config["url"] ?>/static/css/fa-all.min.css">
    <!-- Required Javascript Files -->
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/bootstrap.min.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/application.fn.js"></script>

    <script type="text/javascript">
      var appurl="<?php echo $this->config["url"] ?>";
      var token="<?php echo $this->config["public_token"] ?>";
    </script>
    <?php Main::enqueue() // Add scripts when needed ?>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/application.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/server.js"></script>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body<?php echo Main::body_class() ?> id="body">
    <a href="#body" id="back-to-top"><i class="glyphicon glyphicon-chevron-up"></i></a>
    <?php if($this->isUser): // Show header for logged user ?>
      <header class="app">
        <div class="navbar" role="navigation">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-2">
                <div class="navbar-header">
                  <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse,.sidebar">
                    <i class="glyphicon glyphicon-align-justify"></i>
                  </button>
                  <a class="navbar-brand" href="<?php echo $this->config["url"] ?>">
                    <?php if (!empty($this->config["logo"])): ?>
                    <img src="<?php echo $this->config["url"] ?>/content/<?php echo $this->config["logo"] ?>" alt="<?php echo $this->config["title"] ?>">
                    <?php else: ?>
                      <?php echo $this->config["title"] ?>
                    <?php endif ?>
                  </a>
                </div>
              </div>
              <div class="col-md-10">
                <?php echo $this->menu() ?>
              </div>
            </div>
          </div>
        </div>
      </header>
      <section>
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-2 sidebar">
              <?php echo $this->user_menu() ?>
            </div>
            <div class="col-md-10 content">
    <?php else: ?>
      <?php if($this->headerShow): // Show header ?>
        <header>
          <div class="navbar" role="navigation">
            <div class="container">
              <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                  <span class="glyphicon glyphicon-align-justify"></span>
                </button>
                <a class="navbar-brand" href="<?php echo $this->config["url"] ?>">
                  <?php if (!empty($this->config["logo"])): ?>
                  <img src="<?php echo $this->config["url"] ?>/content/<?php echo $this->config["logo"] ?>" alt="<?php echo $this->config["title"] ?>">
                  <?php else: ?>
                    <?php echo $this->config["title"] ?>
                  <?php endif ?>
                </a>
              </div>
              <?php echo $this->menu() ?>
            </div>
          </div>
        </header>
      <?php endif ?>
    <?php endif ?>