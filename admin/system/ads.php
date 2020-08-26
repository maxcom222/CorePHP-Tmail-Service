<?php if(!defined("APP")) die(); // Protect this page ?>
<?php if(!$this->config["ads"]): ?>
  <div class="panel panel-red panel-body">
    Please note that the advertisement module is disabled. You can enable it via settings > application settings.
  </div>
<?php endif ?>
<div class="panel panel-default">
  <div class="panel-heading">
    Advertisment (<?php echo $count ?>)
    <a href="<?php echo Main::ahref("ads/add") ?>" class="pull-right btn btn-primary btn-xs">Add Advertisment</a>
  </div>  
  <div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th><a href="<?php echo Main::ahref("ads?filter=type") ?>">Type</a></th>
              <th><a href="<?php echo Main::ahref("ads?filter=impression") ?>">Impression</a></th>              
              <th><a href="<?php echo Main::ahref("ads?filter=enabled") ?>">Enabled</a></th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($ads as $ad): ?>
              <tr data-id="<?php echo $ad->id ?>">
                <td><?php echo $ad->name ?></td>
                <td><?php echo ad_type($ad->type) ?></td>
                <td><?php echo $ad->impression ?></td>
                <td><?php echo $ad->enabled ? "Yes" : "No" ?></td>                 
                <td>
                  <a href="<?php echo Main::ahref("ads/edit/{$ad->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("ads/delete/{$ad->id}").Main::nonce("delete_ad-{$ad->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table> 
    </div>
  </div>
</div>