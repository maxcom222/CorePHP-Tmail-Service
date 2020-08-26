<?php if(!defined("APP")) die()?>
<div class="row">
  <div class="col-sm-9">
    <div class="panel panel-default">
      <div class="panel-heading">
        Domains (<?php echo $count ?>)
      </div>      
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Domain</th>
                <th>User</th>
                <th>Status</th>
                <th>Options</th>
              </tr>
            </thead>
            <tbody>          
              <?php foreach ($domains as $domain): ?>
                <?php $user = $this->db->get("user", ["id" => $domain->userid], ["limit" => "1"]) ?>
                <tr data-id="<?php echo $domain->id ?>">
                  <td><a href="<?php echo $domain->domain ?>" target="_blank"><?php echo $domain->domain ?></a></td>
                  <td><a href="<?php echo Main::href("user/view/{$user->id}") ?>"><?php echo $user->email ?></a></td>
                  <td>
                    <?php if($domain->status == "1"): ?>
                      <span class="label label-success">Active</span>
                    <?php elseif($domain->status == "2"): ?>
                      <span class="label label-warning">Pending DNS</span>
                    <?php else: ?>
                      <span class="label label-danger">Inactive/Disabled</span>
                    <?php endif ?>
                  </td>         
                  <td>
                    <?php if($domain->status == "1"): ?>
                      <a href="<?php echo Main::ahref("domains/disable/{$domain->id}").Main::nonce("delete_disable-{$domain->id}") ?>" class="btn btn-danger btn-xs delete" data-content="Disabling this domain will make all URLs stop working and no one else will be able to add this domain ever again.">Disable</a>
                    <?php else: ?>              
                    <a href="<?php echo Main::ahref("domains/activate/{$domain->id}") ?>" class="btn btn-success btn-xs">Activate</a>  
                    <?php endif ?>
                    <a href="<?php echo Main::ahref("domains/pend/{$domain->id}") ?>" class="btn btn-warning btn-xs">Set to pending</a>
                  </td>
                </tr>      
              <?php endforeach ?>
            </tbody>
          </table> 
        </div>
        <?php echo $pagination ?>
      </div>
    </div>     
  </div>
  <div class="col-sm-3">
    <div class="panel panel-default">
      <div class="panel-heading">Enabling Domains</div>
      <div class="panel-body">
        <p>Customers can add their own domain name and use it to shorten URLs. This will require some setup. Your customers can add their own domain name via the Custom Domain page. They will need to either add an A record or a CNAME record. On your side, you will require some changes before your server can accept their domains. If you are using cPanel, add the following the domain and <strong>make sure the directory is the same as current script directory</strong>. If you are on a VPS, please see the documentation via the link below.</p>
        <a href="https://gempixel.com/docs/premium-url-shortener?utm_source=AppAdmin#cd" class="btn btn-primary" target="_blank">Get Help</a>
      </div>
    </div>
  </div>
</div>