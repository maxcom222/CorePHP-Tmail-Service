<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    Blog Posts (<?php echo $count ?>)
    <a href="<?php echo Main::ahref("blog/add") ?>" class="pull-right btn btn-primary btn-xs">Add Post</a>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Permalink</th>
              <th>Published</th>
              <th>Views</th>
              <th>Options</th>
            </tr>
          </thead>
          <tbody>          
            <?php foreach ($posts as $post): ?>
              <tr data-id="<?php echo $post->id ?>">
                <td><?php echo $post->title ?></td>
                <td><a href="<?php echo Main::href("blog/{$post->slug}") ?>" class='btn btn-success btn-xs' target='_blank'><?php echo $post->slug ?></a></td>
                <td><?php echo $post->published ? "Yes" : "No" ?></td>         
                <td><?php echo $post->views ?> views</td>         
                <td>
                  <a href="<?php echo Main::ahref("blog/edit/{$post->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
                  <a href="<?php echo Main::ahref("blog/delete/{$post->id}").Main::nonce("delete_blog-{$post->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>
                </td>
              </tr>      
            <?php endforeach ?>
          </tbody>
        </table> 
    </div>
  </div>
</div>