<?php defined("APP") or die() ?>

<div class="share-this"></div>
<div class="ajax"></div>
<form action="<?php echo Main::href("shorten") ?>" id="main-form" role="form" method="post" autocomplete="off">
  <div class="main-form">
    <div class="row" id="single">
      <div class="col-sm-10">
        <div class="input-group">
          <span class="input-group-addon"><i class="glyphicon glyphicon-link"></i></span>
          <input type="text" class="form-control main-input" name="url" value="<?php if(isset($_GET["url"])) echo Main::clean($_GET["url"]) ?>" placeholder="<?php echo e("Paste a long url") ?>" autocomplete="off" />
        </div>                 
      </div>
      <div class="col-sm-2">
        <button class="btn btn-primary btn-block main-button" id="shortenurl" type="submit"><?php echo e("Shorten") ?></button>
        <button class="btn btn-primary btn-block main-button" id="copyurl" type="button"><?php echo e("Copy") ?></button>
      </div>
    </div>
    <?php if($option["multiple"]): ?>
    <div id="multiple" style="display: none">
      <div class="form-group">
        <textarea class="form-control main-textarea" name="urls" rows="5" placeholder="<?php echo e("Paste up to 10 long urls. One URL per line.") ?>"></textarea>
      </div> 
      <button class="btn btn-primary main-button" id="shortenurl" type="submit"><?php echo e("Shorten") ?></button>         
    </div>        
    <?php endif; ?>
  </div>
  <!-- /.main-form -->
  <div class="main-options clearfix">
    <?php if($option["advanced"]): ?>
      <a href="#" class="btn btn-primary advanced"><?php echo e("Advanced Options")?></a>
    <?php endif; ?>
    <?php echo $this->shortener_option() ?>
  </div><!-- /.main-options -->
  <?php if(!$this->user): ?>
    <div id="captcha" style="display:none">
      <?php echo Main::captcha() ?>
    </div>
  <?php endif ?>
  <?php if($option["advanced"]): ?>    
    <div class="main-advanced<?php if($option["autohide"]) echo " slideup" ?>">
      <div class="row">
        <?php if($this->permission("alias") !== FALSE): ?>
        <div class="col-md-3 col-sm-6">
          <h3><?php echo e("Custom Alias")?></h3>
          <p><?php echo e('If you need a custom alias, you can enter it below.')?></p>
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span>
            <input type="text" class="form-control" name="custom" placeholder="<?php echo e("Type your custom alias here")?>" autocomplete = "off">
          </div>                  
        </div>
        <?php endif ?>
        <!-- /.col-md-3 -->
        <div class="col-md-3 col-sm-6">
          <h3><?php echo e("Link Expiration")?></h3>
          <p><?php echo e('Set an expiration date to disable the URL after this date.')?></p>                  
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
            <input type="text" class="form-control" data-toggle="datepicker" name="expiry" id="expiry" placeholder="<?php echo e("MM/DD/YYYY")?>" autocomplete = "off">
          </div>                  
        </div>
        <!-- /.col-md-3 -->        
        <div class="col-md-3 col-sm-6">
          <h3><?php echo e("Password Protect")?></h3>
          <p><?php echo e('By adding a password, you can restrict the access.')?></p>                  
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
            <input type="text" class="form-control" name="password" id="pass" placeholder="<?php echo e("Type your password here")?>" autocomplete = "off">
          </div>                  
        </div>
        <!-- /.col-md-3 -->
        <div class="col-md-3 col-sm-6">
          <h3><?php echo e("Description")?></h3>
          <p><?php echo e('This can be used to identify URLs on your account.')?></p>                  
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-tag"></i></span>
            <input type="text" class="form-control" name="description" placeholder="<?php echo e("Type your description here")?>" autocomplete = "off">
          </div>                  
        </div>
        <!-- /.col-md-3 -->
      </div><!--/.row -->
      <?php if($this->config["geotarget"] && $this->permission("geo") !== FALSE):?>
        <hr>
        <div class="row geotarget" id="geo">
          <div class="col-md-12 geo-input">
            <h3><?php echo e("Geotargeting")?> <a href="#" class="btn btn-xs btn-primary pull-right add_geo" data-home="true"><?php echo e("Add more locations")?></a></h3>
            <p><?php echo e('If you have different pages for different countries then it is possible to redirect users to that page using the same URL. Simply choose the country and enter the URL.')?></p>           
            <div class="row country">
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                  <select name="location[]" class="form-control">
                    <?php echo Main::countries() ?>
                  </select>
                </div>              
              </div><!-- /.col-md-6 -->  
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-link"></i></span>
                  <input type="text" name="target[]" class="form-control" placeholder="<?php echo e("Type the url to redirect user to.")?>">
                </div>
              </div><!-- /.col-md-6 -->  
            </div><!-- /.row -->
          </div><!-- /.geo-input -->
        </div><!-- /.row -->            
      <?php endif; ?>
      <?php if($this->config["devicetarget"] && $this->permission("device") !== FALSE):?>
        <hr>
        <div class="row devicetarget" id="device">
          <div class="col-md-12 device-input">
            <h3><?php echo e("Device Targeting")?> <a href="#" class="btn btn-xs btn-primary pull-right add_device" data-home="true"><?php echo e("Add more device")?></a></h3>
            <p><?php echo e('If you have different pages for different devices (such as mobile, tablet etc) then it is possible to redirect users to that page using the same short URL. Simply choose the device and enter the URL.')?></p>
            <div class="row devices">
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-phone"></i></span>
                  <select name="device[]" class="form-control">
                    <?php echo Main::devices() ?>
                  </select>
                </div>              
              </div><!-- /.col-md-6 -->  
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-link"></i></span>
                  <input type="text" name="dtarget[]" class="form-control" placeholder="<?php echo e("Type the url to redirect user to.")?>">
                </div>
              </div><!-- /.col-md-6 -->  
            </div><!-- /.row -->
          </div><!-- /.device-input -->
        </div><!-- /.row -->            
      <?php endif; ?>    
      <?php if ($this->permission("pixels") !== FALSE): ?>
        <hr>
        <div class="row" id="pixels">
          <div class="col-md-12">
            <h3><?php echo e("Targeting Pixels")?></h3>
            <p><?php echo e('Add your targeting pixels below from the list. Please make sure to enable them in the pixels settings.')?></p>           
            <div class="row devices">
              <div class="col-md-12">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-filter"></i></span>
                  <select name="pixels[]" data-placeholder="Your Pixels" multiple>
                    <?php if($fbpixel = json_decode($this->user->fbpixel)): ?>
                      <optgroup label="Facebook">
                        <?php foreach ($fbpixel as $key => $ad): ?>
                          <option value="fbpixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>
                    <?php if($adwordspixel = json_decode($this->user->adwordspixel)): ?>
                      <optgroup label="Adwords">
                        <?php foreach ($adwordspixel as $key => $ad): ?>
                          <option value="adwordspixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>
                    <?php if($linkedinpixel = json_decode($this->user->linkedinpixel)): ?>
                      <optgroup label="LinkedIn">
                        <?php foreach ($linkedinpixel as $key => $ad): ?>
                          <option value="linkedinpixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>
                    <?php if($twitterpixel = json_decode($this->user->twitterpixel)): ?>
                      <optgroup label="Twitter">
                        <?php foreach ($twitterpixel as $key => $ad): ?>
                          <option value="twitterpixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>
                    <?php if($adrollpixel = json_decode($this->user->adrollpixel)): ?>
                      <optgroup label="AdRoll">
                        <?php foreach ($adrollpixel as $key => $ad): ?>
                          <option value="adrollpixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>
                    <?php if($quorapixel = json_decode($this->user->quorapixel)): ?>
                      <optgroup label="Quora">
                        <?php foreach ($quorapixel as $key => $ad): ?>
                          <option value="quorapixel-<?php echo $key ?>"><?php echo $ad->name ?></option>
                        <?php endforeach ?>
                      </optgroup>
                    <?php endif ?>                    
                  </select>
                </div>              
              </div><!-- /.col-md-6 -->  
            </div><!-- /.row -->
          </div><!-- /.device-input -->
        </div><!-- /.row -->              
      <?php endif ?>  
      <?php if ($this->permission("parameters") !== FALSE): ?>
        <hr>
        <div class="row" id="parameters">
          <div class="col-md-12 parameter-input">
            <h3><?php echo e("Parameter Builder")?> <a href="#" class="btn btn-xs btn-primary pull-right add_parameter" data-home="true"><?php echo e("Add parameters")?></a></h3>
            <p><?php echo e("You can add custom parameters to the link above using this tool. Choose the parameter name and then assign a value. These will be added during redirection.")?></p>
            <div class="row parameters">
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-list-alt"></i></span>
                  <input type="text" name="paramname[]" class="form-control autofillparam" placeholder="<?php echo e("Parameter name")?>">
                </div>              
              </div><!-- /.col-md-6 -->  
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span>
                  <input type="text" name="paramvalue[]" class="form-control" placeholder="<?php echo e("Parameter value")?>">
                </div>
              </div><!-- /.col-md-6 -->  
            </div><!-- /.row -->
          </div><!-- /.device-input -->
        </div><!-- /.row -->           
      <?php endif ?>
    </div><!-- /.main-advanced -->  
  <?php endif ?>
  <input type="hidden" value="0" name="multiple" id="multiple-form">
  <input type="hidden" value="<?php echo md5($this->config["public_token"]) ?>">
</form><!--/.form-->  
<?php if($option["multiple"]): ?>
<ul class="form_opt" data-id="multiple-form" data-callback="form_switch">
  <li><a href="" class="last" data-value="1"><?php echo e("Multiple")?></a></li>
  <li><a href="" class="first current" data-value="0"><?php echo e("Single")?></a></li>
</ul>
<?php endif ?>