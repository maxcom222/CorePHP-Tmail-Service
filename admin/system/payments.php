<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Payments <?php echo $count ?>
    <a href="<?php echo Main::ahref("payments/export").Main::nonce("export") ?>" class="pull-right btn btn-primary btn-xs">Export</a>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Transaction ID</th>
            <th>User ID</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Expiration</th>
          </tr>
        </thead>
        <tbody>          
          <?php foreach ($payments as $payment): ?>
            <tr data-id="<?php echo $payment->id ?>">
              <td><?php echo $payment->id ?></td>
              <td><?php echo $payment->tid ?> <?php echo (($payment->status == "Refunded") ? "(Refund)" : "") ?></td>
              <td><a href="<?php echo Main::ahref("users/edit/{$payment->userid}")?>" class="btn btn-success btn-xs"><?php echo $payment->userid ?></a></td>
              <td><?php echo $payment->status ?></td>
              <td><?php echo (($payment->status == "Refunded") ? "-" : "") ?><?php echo $payment->amount ?></td>
              <td><?php echo $payment->date ?></td>
              <td><?php echo $payment->expiry ?></td>
            </tr>      
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php echo $pagination ?>
  </div>
</div>