<?php if(!defined("APP")) die()?>
<!DOCTYPE html>
<html lang="en">
  <head>    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0" />  
    <meta name="description" content="<?php echo Main::description() ?>" />
    
    
    <title><?php echo Main::title() ?></title>
    <!-- Bootstrap core CSS -->
    <link href="<?php echo $this->config["url"] ?>/static/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->url ?>/static/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->config["url"] ?>/static/css/components.min.css">

    <!-- Javascript Files -->
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jquery.min.js?v=1.11.0"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/chosen.min.js?v=0.8.5"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/application.fn.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/bootstrap.min.js"></script>    
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jvector.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jvector.world.js"></script>
    <script type="text/javascript">
      var appurl="<?php echo $this->url ?>";
    </script>
    <script type="text/javascript" src="<?php echo $this->url ?>/static/dashboard.js"></script>
    <?php Main::admin_enqueue() ?>    
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body id="main">
    <a href="#main" id="back-to-top">Back to top</a>
    <div class="navbar" role="navigation">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-2">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="glyphicon glyphicon-align-justify"></span>
              </button>
              <a class="navbar-brand" href="<?php echo $this->url ?>"><?php echo $this->config["title"] ?></a>
            </div>            
          </div>
          <div class="navbar-collapse collapse">         
            <form class="navbar-form navbar-left search" action="<?php echo Main::ahref("search") ?>">
              <input type="text" class="form-control" size="80" placeholder="Search for users, urls or payments and press enter." name="q">
            </form>             
            <ul class="nav navbar-nav navbar-right">
              <?php if (_VERSION < $this->update_notification(true)[0]): ?>
                <li><a href="<?php echo Main::ahref("update") ?>"><span class="glyphicon glyphicon-bell"></span> New Update</a></li>
              <?php endif ?>              
              <li><a href="<?php echo $this->config["url"] ?>" target="_blank"><span class="glyphicon glyphicon-globe"></span> View Site</a></li>
              <li><a href="<?php echo Main::href("user/logout") ?>"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
            </ul>            
          </div>        
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row">
        <div class="col-md-2 sidebar">
          <ul class="nav nav-sidebar">
            <li class="active"><a href="<?php echo $this->url ?>/"><span class="glyphicon glyphicon-dashboard"></span> Dashboard</a></li>
            <li><a href="<?php echo Main::ahref("urls") ?>"><span class="glyphicon glyphicon-link"></span> URLs</a></li>
            <li><a href="<?php echo Main::ahref("users")?>"><span class="glyphicon glyphicon-user"></span> Users</a>
              <div>       
                <a href="<?php echo Main::ahref("users/add")?>"><span class="glyphicon glyphicon-plus"></span> Add a User</a>
              </div>
            </li>        
            <li><a href="<?php echo Main::ahref("plans") ?>"><span class="glyphicon glyphicon-briefcase"></span> Plans</a>
              <div>       
                <a href="<?php echo Main::ahref("plans/add")?>"><span class="glyphicon glyphicon-plus"></span> Add a Plan</a>                
              </div>
            </li>
            <li><a href="<?php echo Main::ahref("payments") ?>"><span class="glyphicon glyphicon-credit-card"></span> Payments</a></li>
            <li><a href="<?php echo Main::ahref("subscription") ?>"><span class="glyphicon glyphicon-refresh"></span> Subscriptions</a></li>
            <li><a href="<?php echo Main::ahref("domains") ?>"><span class="glyphicon glyphicon-cloud"></span> Domains</a></li>
            <li><a href="<?php echo Main::ahref("blog")?>"><span class="glyphicon glyphicon-list-alt"></span> Blog</a>
              <div>       
                <a href="<?php echo Main::ahref("blog/add")?>"><span class="glyphicon glyphicon-plus"></span> Add a Post</a>
              </div>
            </li>            
            <li><a href="<?php echo Main::ahref("pages")?>"><span class="glyphicon glyphicon-book"></span> Pages</a>
              <div>       
                <a href="<?php echo Main::ahref("pages/add")?>"><span class="glyphicon glyphicon-plus"></span> Add a Page</a>
              </div>
            </li>
            <li><a href="<?php echo Main::ahref("ads")?>"><span class="glyphicon glyphicon-usd"></span> Advertisement</a>
              <div>       
                <a href="<?php echo Main::ahref("ads/add")?>"><span class="glyphicon glyphicon-plus"></span> Add an ad</a>
              </div>
            </li>                
            <li><a href="<?php echo Main::ahref("themes") ?>"><span class="glyphicon glyphicon-eye-open"></span> Themes</a>
              <div>       
                <a href="<?php echo Main::ahref("themes/editor")?>"><span class="glyphicon glyphicon-pencil"></span> Editor</a>
                <?php if ($this->hasOptions()): ?>
                  <a href="<?php echo Main::ahref("themes/options")?>"><span class="glyphicon glyphicon-cog"></span> Options</a>
                <?php endif ?>
              </div>
            </li>
            <li><a href="<?php echo Main::ahref("languages") ?>"><span class="glyphicon glyphicon-globe"></span> Languages</a>
              <div>       
                <a href="<?php echo Main::ahref("languages/add")?>"><span class="glyphicon glyphicon-plus"></span> Add Language</a>
              </div>
            </li>
            <li><a href="<?php echo Main::ahref("settings") ?>"><span class="glyphicon glyphicon-cog"></span> Settings</a>
              <div>       
                <a href="<?php echo Main::ahref("emails")?>"><span class="glyphicon glyphicon-email"></span> Emails</a>
              </div>              
            </li> 
            <li><a href="<?php echo Main::ahref("tools")?>"><span class="glyphicon glyphicon-wrench"></span> Tools</a>
              <div>
                <a href="<?php echo Main::ahref("tools/newsletter")?>">Send Newsletter</a>
              </div>
            </li>
            <li><a href="<?php echo Main::ahref("update") ?>"><span class="glyphicon glyphicon-cloud-download"></span> Update</a></li> 
          </ul>
        </div>
        <div class="col-md-10 main">
          <?php echo Main::message() ?>