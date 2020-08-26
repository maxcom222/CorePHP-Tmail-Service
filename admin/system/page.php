<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Custom Pages (<?php echo $count ?>)
    <a href="<?php echo Main::ahref("pages/add") ?>" class="pull-right btn btn-primary btn-xs">Add Page</a>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Permalink</th>
              <th>Content</th>
              <th>In Menu</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($pages as $page): ?>
              <tr data-id="<?php echo $page->id ?>">
                <td><?php echo Main::truncate($page->name,20) ?></td>
                <td><a href="<?php echo Main::href("page/{$page->seo}") ?>" class='btn btn-success btn-xs' target='_blank'><?php echo $page->seo ?></a></td>
                <td><?php echo Main::truncate(strip_tags($page->content),100) ?></td>
                <td><?php echo $page->menu ? "Yes" : "No" ?></td>         
                <td>
                  <a href="<?php echo Main::ahref("pages/edit/{$page->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("pages/delete/{$page->id}").Main::nonce("delete_page-{$page->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table> 
    </div>
  </div>
</div>