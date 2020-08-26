<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Languages
    <a href="<?php echo Main::ahref("languages/add") ?>" class="pull-right btn btn-primary btn-xs">Add Language</a>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Author</th>
              <th>Date</th>
              <th>% Translated</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($languages as $language): ?>
              <tr>
                <td><?php echo $language["name"] ?></td>
                <td><?php echo $language["code"] ?></td>
                <td><?php echo $language["author"] ?></td>
                <td><?php echo $language["date"] ?></td>
                <td><span class="label label-success"><?php echo $language["percent"] ?>%</span></td>
                <td>
                  <a href="<?php echo Main::ahref("languages/edit/{$language["code"]}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("languages/delete/{$language["code"]}").Main::nonce("delete_language-{$language["code"]}") ?>" class="btn btn-danger btn-xs delete" title="Delete Language">Delete</a>                  
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table> 
    </div>
  </div>
</div>