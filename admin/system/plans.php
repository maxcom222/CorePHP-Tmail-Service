<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Membership Plans (<?php echo $count ?>)
    <a href="<?php echo Main::ahref("plans/add") ?>" class="pull-right btn btn-primary btn-xs">Add Plan</a>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Price Monthly</th>
              <th>Price Yearly</th>
              <th>Permissions</th>
              <th>Status</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($plans as $plan): ?>
              <tr data-id="<?php echo $plan->id ?>">
                <td><?php echo Main::truncate($plan->name,20) ?></td>
                <?php if ($plan->free): ?>
                  <td>Free</td>
                  <td>Free</td>
                <?php else: ?>
                  <td><?php echo Main::currency($this->config["currency"]) ?> <?php echo $plan->price_monthly ?></td>
                  <td><?php echo Main::currency($this->config["currency"]) ?> <?php echo $plan->price_yearly ?></td>
                <?php endif ?>
                <td>
                  <span class="label label-info"><?php echo $plan->numurls == "0" ? "Unlimited" : $plan->numurls ?> urls</span>                  
                  <?php foreach (json_decode($plan->permission) as $type => $p): ?>
                    <?php if (isset($p->enabled) && $p->enabled): ?>
                      <?php $count = NULL;
                        if (isset($p->count)): ?>
                        <?php $count = $p->count == "0" ? "Unlimited" : $p->count ?>
                      <?php endif ?>
                      <span class="label label-info"><?php echo $count ?> <?php echo $type == "api" ? "API Access" : ucfirst($type) ?></span>
                    <?php endif ?>
                  <?php endforeach ?>
                </td>
                <td><?php echo $plan->status ? "<span class='label label-success'>Active</span>" : "<span class='label label-danger'>Inactive</span>" ?></td>         
                <td>
                  <a href="<?php echo Main::ahref("plans/edit/{$plan->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("plans/delete/{$plan->id}").Main::nonce("delete_plan-{$plan->id}") ?>" class="btn btn-danger btn-xs delete" data-content="If you are using Stripe, deleting a plan will not affect existing subscribers! They will continue to be charged. To stop users from being charged you will need to cancel their membership first. Delete plans only if you don't have any other options!">Delete</a>
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table> 
    </div>
  </div>
</div>