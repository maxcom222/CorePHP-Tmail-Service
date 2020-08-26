<?php 

spl_autoload_register(function($class)
{

  $file = ROOT."/includes/library/geoip/".str_replace('\\', '/', $class) . '.php';
  
  if (file_exists($file)) {
    require $file;
  }
});

use MaxMind\Db\Reader;
