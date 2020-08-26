<?php defined("APP") or die() // Footer ?>
  <?php if ($this->isUser): // Show user page footer ?>
              <footer class="main">
                <div class="row">
                  <div class="col-md-5">
                    <?php echo date("Y") ?> &copy; <?php echo $this->config["title"] ?>.
                  </div>
                  <div class="col-md-7 text-right">                                      
                    <?php foreach ($pages as $page):?>        
                      <a href='<?php echo $this->config["url"]?>/page/<?php echo $page->seo ?>' title='<?php echo e($page->name)?>'><?php echo e($page->name)?></a>
                    <?php endforeach; ?>
                    <?php if ($this->config["blog"]): ?>
                      <a href="<?php echo $this->config["url"]?>/blog" title='<?php echo e("Blog")?>'><?php echo e("Blog")?></a> 
                    <?php endif ?>
                    <a href='<?php echo $this->config["url"]?>/contact' title='<?php echo e("Contact")?>'><?php echo e("Contact")?></a> 
                    <div class="languages">
                      <a href="#lang" class="active" id="show-language"><i class="glyphicon glyphicon-globe"></i> <?php echo e("Language") ?></a>
                      <div class="langs">
                        <?php echo $this->lang(0) ?>
                      </div>          
                    </div>                      
                  </div>
                </div>
            </footer>  
          </div><!--/.content-->
        </div><!--/.row-->
      </div><!--/.container-->      
    </section>
  <?php else: // Show general footer ?>
    <?php if ($this->footerShow): ?>
      <footer class="main">
        <div class="container">
          <div class="row">
            <div class="col-md-5">
              <?php echo date("Y") ?> &copy; <?php echo $this->config["title"] ?>.
            </div>
            <div class="col-md-7 text-right">
              <?php foreach ($pages as $page):?>        
                <a href='<?php echo $this->config["url"]?>/page/<?php echo $page->seo ?>' title='<?php echo e($page->name)?>'><?php echo e($page->name)?></a>
              <?php endforeach; ?>
              <?php if ($this->config["blog"]): ?>
                <a href="<?php echo $this->config["url"]?>/blog" title='<?php echo e("Blog")?>'><?php echo e("Blog")?></a> 
              <?php endif ?>              
              <a href='<?php echo $this->config["url"]?>/contact' title='<?php echo e("Contact")?>'><?php echo e("Contact")?></a>
              <div class="languages">
                <a href="#lang" class="active" id="show-language"><i class="glyphicon glyphicon-globe"></i> <?php echo e("Language") ?></a>
                <div class="langs">
                  <?php echo $this->lang(0) ?>
                </div>          
              </div>                            
            </div>
          </div>
        </div>
      </footer>      
    <?php endif ?>
  <?php endif ?>   
  <script type="text/javascript">
    <?php 
      $js_lang = array(
        "del" => e("Delete"),
        "url" => e("URL"),
        "count" => e("Country"),
        "copied"  =>  e("Copied"),
        "geo" => e("Geotargeting data for"),
        "error" => e('Please enter a valid URL.'),
        "success" => e('URL has been successfully shortened. Click Copy or CRTL+C to Copy it.'),
        "stats" => e('You can access the statistic page via this link'),
        "copy" => e('Copied to clipboard.'),
        "from" => e('Redirect from'),
        "to" => e('Redirect to'),
        "share" => e('Share this on'),
        "congrats"  => e('Congratulation! Your URL has been successfully shortened. You can share it on Facebook or Twitter by clicking the links below.'),
        "qr" => e('Save QR Code'),
        "continue"  =>  e("Continue"),
        "cookie" => e("This website uses cookies to ensure you get the best experience on our website."),
        "cookieok" => e("Got it!"),
        "cookiemore" => e("Learn more")
      );
    ?>
    var lang = <?php echo json_encode($js_lang) ?>;
  </script>  
	<?php Main::enqueue('footer') ?>
  <script type="text/javascript" src="<?php echo $this->theme("assets/js/main.js") ?>"></script>
	</body>
</html>