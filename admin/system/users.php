<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Users <?php echo $count ?>
    <div class="btn-group pull-right">
      <a href="<?php echo Main::ahref("users/add") ?>" class="btn btn-primary btn-xs">Add User</a>     
    </div>
  </div>
  <div class="panel-body">
    <form action="<?php echo Main::ahref("users/delete")?>" method="post" id="delete-all-urls">
      <p class="cta-hide alert alert-warning">Please note that using the mass delete feature will only delete users. Their URLs will still be in the system and will be made public.</p>
      <div class="row">
        <div class="col-md-10">
         <input type='submit' class="btn btn-danger" value='Delete Selected'>
        </div>
        <?php if (!isset($hideFilter)): ?>
          <div class="col-md-2">
            <select name="filter" id="filter" class="hidden-xs" data-search="0">
              <option value=""<?php if(Main::is_set('filter','')) echo " selected" ?>>Newest</option>
              <option value="old"<?php if(Main::is_set('filter','old')) echo " selected" ?>>Oldest</option>
              <option value="admin"<?php if(Main::is_set('filter','admin')) echo " selected" ?>>Admin Only</option>
            </select>          
          </div> 
        <?php endif ?> 
      </div>
      <br>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th><input type="checkbox" id="check-all"></th>
              <th>Email</th>
              <th>User Status</th>
              <th>Registration Date</th>
              <th>Membership</th>
              <th>Expiration</th>
              <th>Number of URLs</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($users as $user): ?>
              <?php if($this->config["demo"]) $user->email="Hidden in demo to protect privacy"; ?>
              <?php if(empty($user->email)) $user->email=ucfirst($user->auth)." User" ?>
              <?php $user->count=$this->db->count("url","userid='{$user->id}'") ?>
              <tr data-id="<?php echo $user->id ?>">
                <td><input type="checkbox" class="data-delete-check" name="delete-id[]" value="<?php echo $user->id ?>"></td>
                <td><?php echo ($user->admin)?"<strong>{$user->email}</strong>":$user->email ?></td>
                <td><?php echo ($user->active?"Active":"Not Active") ?></td>                
                <td><?php echo date("F d, Y",strtotime($user->date)) ?></td>
                <td><?php echo ($user->pro?"Pro":"Free") ?></td>
                <td><?php echo ($user->pro?date("F d, Y",strtotime($user->expiration)):"n/a") ?></td>                
                <td><a href="<?php echo Main::ahref("urls/view/{$user->id}") ?>" class="btn btn-success btn-xs"><?php echo $user->count ?></a></td>
                <td>
                  <a href="<?php echo Main::ahref("payments/view/{$user->id}") ?>" class="btn btn-success btn-xs">View Payments</a>
                  <a href="<?php echo Main::ahref("users/edit/{$user->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("users/delete/{$user->id}").Main::nonce("delete_user-{$user->id}") ?>" class="btn btn-danger btn-xs delete" title="Delete user only">Delete User</a>
                  <a href="<?php echo Main::ahref("users/delete/{$user->id}").Main::nonce("delete_user_all-{$user->id}") ?>" class="btn btn-danger btn-xs delete" title="Delete user and all associated URLs">Delete All</a>
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table>
        <?php echo Main::csrf_token(TRUE) ?>     
      </div>
    </form>    
    <?php echo $pagination ?>   
  </div>
</div>