<?php if(!defined("APP")) die()?>
<?php if (installer()): ?>
  <p class="alert alert-danger">
    <strong>Danger</strong> You must delete the file install.php right now from your root directory.
  </p>  
<?php endif ?>
<div class="row stats">
  <div class="col-md-3">
    <div class="panel panel-default panel-blue">
      <div class="panel-body">
        <p class="main-stats"><span><?php echo $this->db->count("url") ?></span> URLs</p>
        <p>+ <?php echo $this->db->count("url","date>=curdate()") ?> Today</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="panel panel-default panel-green">
      <div class="panel-body">
        <p class="main-stats"><span><?php echo $this->db->count("url","","click") ?></span> Clicks</p>
        <p>+ <?php echo $this->config["tracking"]=="2" ? "Not Available" : $this->db->count("stats","date>=curdate()")." Today" ?></p>
      </div>
    </div>
  </div> 
  <div class="col-md-3">
    <div class="panel panel-default panel-red">
      <div class="panel-body">
        <p class="main-stats"><span><?php echo $this->db->count("user") ?></span>Users</p>
        <p>+ <?php echo $this->db->count("user","date>=curdate()") ?> Today</p>
      </div>
    </div>
  </div>  
  <div class="col-md-3">
    <div class="panel panel-default panel-black">
      <div class="panel-body">
        <p class="main-stats"><span><?php echo Main::currency($this->config["currency"],$this->db->count("payment","(MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())) AND status='Completed'","amount") - $this->db->count("payment","(MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())) AND status='Refunded'","amount")) ?></span> in <?php echo date("F") ?></p>
         <p><?php echo Main::currency($this->config["currency"],$this->db->count("payment","status='Completed'","amount") - $this->db->count("payment","status='Refunded'","amount")) ?> Total</p>
      </div>
    </div>
  </div>       
</div><!--/.stats-->
<div class="panel panel-default panel-dark hidden-xs">
  <div class="panel-heading">
    Summary Chart
    <div class="btn-group btn-group-xs pull-right">
      <a href="?filter=daily" class="btn btn-primary">Daily</a>
      <a href="?filter=monthly" class="btn btn-primary">Monthly</a>
      <a href="?filter=yearly" class="btn btn-primary">Yearly</a>
    </div>
  </div> 
  <div class="panel-body">
    <div id="user-chart" class='chart'></div>  
  </div>
</div>
<div class="panel panel-default panel-dark">
  <div class="panel-heading">Country Analysis</div>      
  <div class="panel-body">
    <div class="col-md-6">
      <div id="country-map" class='chart'></div>
    </div>
    <div class="col-md-6">
      <ol class="countries">
      <?php foreach ($topcountries as $country => $count):?>
        <li><?php echo $country ?> <span class="label label-primary pull-right"><?php echo $count ?></span></li>
      <?php endforeach ?>
      </ol>
    </div>     
  </div>  
</div>    
<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">Top URLs</div>      
      <div class="panel-body nopadding">
        <?php foreach ($top_urls as $url): ?>
          <div class="url-list">
            <div class="title">
              <img src="<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?>/ico" alt="Favicon">
              <a href="<?php echo $url->url ?>" target="_blank">
                <?php echo Main::truncate(empty($url->meta_title)?$url->url:$url->meta_title,45) ?>
                <span class="pull-right"><?php echo Main::timeago($url->date) ?></span>
              </a> 
            </div>
            <a href="<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?>">
              <strong><?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?></strong>
            </a>
            <a href="<?php echo $this->config["url"]."/".$url->custom.$url->alias ?>+"><strong><?php echo $url->click ?> <small>clicks</small></strong></a>
            <div class="pull-right action">
              <a href="<?php echo Main::ahref("urls/edit/{$url->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
              <a href="<?php echo Main::ahref("urls/delete/{$url->id}").Main::nonce("delete_url-{$url->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>              
            </div>
          </div><!-- /.url-list -->                   
        <?php endforeach ?>      
      </div>
    </div>
  </div>  
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">Latest URLs</div>      
      <div class="panel-body nopadding">
        <?php foreach ($urls as $url): ?>
          <div class="url-list">
            <div class="title">
              <img src="<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?>/ico" alt="Favicon">
              <a href="<?php echo $url->url ?>" target="_blank">
                <?php echo Main::truncate(empty($url->meta_title)?$url->url:$url->meta_title,45) ?>
                <span class="pull-right"><?php echo Main::timeago($url->date) ?></span>
              </a> 
            </div>
            <a href="<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?>" target="_blank">
              <strong><?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?></strong>
            </a>
            <a href="<?php echo $this->config["url"]."/".$url->custom.$url->alias ?>+" target="_blank"><strong><?php echo $url->click ?> <small>clicks</small></strong></a>
            <div class="pull-right action">
              <a href="<?php echo Main::ahref("urls/edit/{$url->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
              <a href="<?php echo Main::ahref("urls/delete/{$url->id}").Main::nonce("delete_url-{$url->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>    
            </div>          
          </div><!-- /.url-list -->                   
        <?php endforeach ?>      
      </div>
    </div>
  </div>      
</div>
<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">Latest Users</div>      
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Email</th>
                <th>User Status</th>                
                <th>Registration Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
              <?php if($this->config["demo"]) $user->email="Hidden in demo to protect privacy"; ?>
              <?php if(empty($user->email)) $user->email=ucfirst($user->auth)." User" ?>
                <tr>
                  <td><?php echo ($user->admin)?"<strong>{$user->email}</strong>":$user->email ?></td>
                  <td><?php echo ($user->active?"Active":"Not Active") ?></td>                  
                  <td><?php echo date("F d, Y",strtotime($user->date)) ?></td>
                </tr>                  
              <?php endforeach ?>     
            </tbody>
          </table>
        </div>         
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">Latest Payments</div>      
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Transaction ID</th>
                <th>User</th>
                <th>Date</th>                
                <th>Expiry</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $payment): ?>
                <tr>
                  <td><?php echo $payment->tid ?></td>
                  <td><a href="<?php echo Main::ahref("user/edit/{$payment->userid}") ?>"><?php echo $payment->userid ?></a></td>
                  <td><?php echo date("M d, Y - H:i",strtotime($payment->date)) ?></td>
                  <td><?php echo date("M d, Y - H:i",strtotime($payment->expiry)) ?></td>
                  <td><?php echo $payment->amount ?></td>
                </tr>                  
              <?php endforeach ?>  
            </tbody>
          </table>
        </div>    
      </div>
    </div>    
  </div>  
</div>