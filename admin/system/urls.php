<?php if(!defined("APP")) die()?>
<div class="panel panel-default">
  <div class="panel-heading">
    <?php if(!empty($this->id)) echo "User's " ?>URLs
  </div>      
  <div class="panel-body nopadding">
    <form action="<?php echo Main::ahref("urls/delete")?>" method="post" id="delete-all-urls">
    	<div class="toolbar">
        <div class="btn-group">
          <a href="#check-all" class="btn btn-primary" id="check-all">Select All</a>
          <input type='submit' class="btn btn-danger" value='Delete Selected'>
        </div>  
        <?php if (!isset($hidePerPage)): ?>
         <select name="perpage" class="hidden-xs" id="perpage" data-search="0">
            <optgroup label="Show per page">
              <option value=""<?php if(Main::is_set('perpage','')) echo " selected" ?>>25</option>
              <option value="50"<?php if(Main::is_set('perpage','50')) echo " selected" ?>>50</option>
              <option value="100"<?php if(Main::is_set('perpage','100')) echo " selected" ?>>100</option>
            </optgroup>
          </select>
        <?php endif ?>              
        <?php if (!isset($hideFilter)): ?>
          <select name="filter" class="hidden-xs" id="filter" data-search="0">
            <optgroup label="Sort by">
              <option value=""<?php if(Main::is_set('filter','')) echo " selected" ?>>Newest</option>
              <option value="old"<?php if(Main::is_set('filter','old')) echo " selected" ?>>Oldest</option>
              <option value="most"<?php if(Main::is_set('filter','most')) echo " selected" ?>>Most Popular</option>
              <option value="less"<?php if(Main::is_set('filter','less')) echo " selected" ?>>Less Popular</option>       
              <option value="anon"<?php if(Main::is_set('filter','anon')) echo " selected" ?>>Anonymous URls</option>
            </optgroup>
          </select>          
        <?php endif ?>
    	</div>
    	<?php foreach ($urls as $url): ?>
        <div class="url-list">          
          <div class="title">
            <div class="url-checkbox">
              <input type="checkbox" class="data-delete-check" value="<?php echo $url->custom.$url->alias ?>" name="delete-id[]">
            </div>
            <img src="https://www.google.com/s2/favicons?domain=<?php echo $url->url ?>" alt="Favicon">
            <a href="<?php echo $url->url ?>" target="_blank">
              <?php echo Main::truncate(empty($url->meta_title)?$url->url:$url->meta_title,50) ?>
              <span class="pull-right"><?php echo Main::timeago($url->date) ?></span>
            </a> 
          </div>
          <div class="url-action">
            <a href="<?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?>">
              <strong><?php echo (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->custom.$url->alias ?></strong>
            </a>
            <a href="<?php echo $this->config["url"]."/".$url->custom.$url->alias ?>+"><strong><?php echo $url->click ?> <small>clicks</small></strong></a>
            <?php if ($url->userid): ?>
              <a href="<?php echo Main::ahref("users/edit/{$url->userid}") ?>"><strong><small>View User</small></strong></a>
            <?php else: ?>
            <i class="glyphicon glyphicon-user"></i> Anonymous
            <?php endif ?>
            <?php if (!empty($url->location)): ?>
              <i class="glyphicon glyphicon-globe"></i> Geotargeted
            <?php endif ?>
            <?php if (!empty($url->pass)): ?>
              <i class="glyphicon glyphicon-lock"></i> Protected
            <?php endif ?> 

            <div class="pull-right action">
              <a href="<?php echo Main::ahref("urls/edit/{$url->id}") ?>" class="btn btn-primary btn-xs">Edit</a>
              <a href="<?php echo Main::ahref("urls/delete/{$url->id}").Main::nonce("delete_url-{$url->id}") ?>" class="btn btn-danger btn-xs delete">Delete</a>              
            </div>          
          </div>
        </div><!-- /.url-list -->    		
    	<?php endforeach ?>  
      <?php echo Main::csrf_token(TRUE) ?>
    </form>
  	<?php echo $pagination ?>
  </div>
</div>