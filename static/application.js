/**
 *  Premium URL Shortener jQuery Application
 *  Copyright @KBRmedia - All rights Reserved 
 */
$(function() {
    if($('[data-toggle="datepicker"]').length > 0){
      $('[data-toggle="datepicker"]').datepicker({
        autoPick: true,
        format: "mm/dd/yyyy",
        startDate: new Date()
      }).val("");    
    }
    $(".tabbed").hide();
    $(".tabbed").filter(":first").fadeIn();
    $(".tabs a").click(function(e){
      if($(this).attr("data-link")){
        return;
      }
      e.preventDefault();
      var id = $(this).attr("href");
      $(".tabs li").removeClass("active");
      $(this).parent("li").addClass("active");
      $(".tabbed").hide();
      $(id).fadeIn();
      update_sidebar();
    });
 /**
  * Hide advanced option + Toggle on click
  */
  $(".slideup").slideUp();
  $(".advanced").click(function(e){
    e.preventDefault();
    $(".main-advanced").slideToggle("medium", function(){
      update_sidebar();
    });
  });  
  /**
   * Add & Delete Location Field
   */
  var html=$(".country").html();
  $(".add_geo").click(function(){
    if($(this).attr("data-home")){
      $(".geo-input").append("<div class='row'>"+html+"</div><p><a href='#' class='btn btn-danger btn-xs delete_geo' data-holder='div.row'>"+lang.del+"</a></p>");
    }else{
      $('#geo').append("<div class='form-group'>"+html+"</div><p><a href='#' class='btn btn-danger btn-xs delete_geo'>"+lang.del+"</a></p>");      
    }
    update_sidebar();
    if($().chosen) {
      $("select").chosen({disable_search_threshold: 5});
    }
    return false;
  }); 
  $(document).on('click',".delete_geo",function(e){
    e.preventDefault();
    var t=$(this);
    $(this).parent('p').prev($("this").attr("data-holder")).slideUp('slow',function(){
      $(this).remove();
      t.parent('p').remove();
    });
    return false;
  });  
  // Add more devices
  var dhtml=$(".devices").html();
  $(".add_device").click(function(){
    if($(this).attr("data-home")){
      $(".device-input").append("<div class='row'>"+dhtml+"</div><p><a href='#' class='btn btn-danger btn-xs delete_device' data-holder='div.row'>"+lang.del+"</a></p>");
    }else{
      $('#device').append("<div class='form-group'>"+dhtml+"</div><p><a href='#' class='btn btn-danger btn-xs delete_device'>"+lang.del+"</a></p>");      
    }
    update_sidebar();
    if($().chosen) {
      $("select").chosen({disable_search_threshold: 5});
    }
    return false;
  }); 
  $(document).on('click',".delete_device",function(e){
    e.preventDefault();
    var t=$(this);
    $(this).parent('p').prev($("this").attr("data-holder")).slideUp('slow',function(){
      $(this).remove();
      t.parent('p').remove();
    });
    return false;
  });  
  // Add more parameters
  var phtml=$(".parameters").html();
  $(".add_parameter").click(function(){
    if($(this).attr("data-home")){
      $(".parameter-input").append("<div class='row'>"+phtml+"</div><p><a href='#' class='btn btn-danger btn-xs delete_parameter' data-holder='div.row'>"+lang.del+"</a></p>");
    }else{
      $('#parameters').append("<div class='form-group'>"+phtml+"</div><p><a href='#' class='btn btn-danger btn-xs delete_parameter'>"+lang.del+"</a></p>");      
    }
    update_sidebar();
    update_autocomplete();
    if($().chosen) {
      $("select").chosen({disable_search_threshold: 5});
    }    
    return false;
  }); 
  $(document).on('click',".delete_parameter",function(e){
    e.preventDefault();
    var t=$(this);
    $(this).parent('p').prev($("this").attr("data-holder")).slideUp('slow',function(){
      $(this).remove();
      t.parent('p').remove();
    });
    return false;
  });  
  /**
   * Call Neo
   **/
  if($().chosen) {
    $("select").chosen({disable_search_threshold: 5});  
  }   
  /**
   * Custom Radio Box
   **/
  $(document).on('click','.form_opt li a',function(e) {
    
    var href=$(this).attr('href');
    var name = $(this).parent("li").parent("ul").attr("data-id");
    var to = $(this).attr("data-value");
    var callback=$(this).parent("li").parent("ul").attr("data-callback");
    if(href=="#" || href=="") e.preventDefault();

    $("input#" + name).val(to);
    $(this).parent("li").parent("ul").find("a").removeClass("current");
    $(this).addClass("current");
    if(callback !==undefined){
      window[callback](to);
    }
  });
  /**
   * Show forgot password form
   **/
   $(document).on('click','#forgot-password',function(){
      show_forgot_password();
   });
   if(location.hash=="#forgot"){
      show_forgot_password();
   }   
   $(document).on('click',"div.alert",function(){
    $(this).fadeOut();
   }); 
  /**
   * Open share window
   */
   $(document).on('click',"a.u_share",function(e){
    e.preventDefault();
    window.open($(this).attr("href"), '', 'left=50%, top=100, width=550, height=450, personalbar=0, toolbar=0, scrollbars=1, resizable=1')    
   });  
  /**
   * Back to top
   */
  $(window).scroll(function(){   
    if(window.pageYOffset>300){
      $("#back-to-top").fadeIn('slow');
    }else{
      $("#back-to-top").fadeOut('slow');
    }
  });
  $("a#back-to-top,.scroll").smoothscroll(); 
  //
  $(document).on('click',".clear-search",function(e){
    e.preventDefault();
    $(".return-ajax").slideUp('medium',function(){
      $(this).html('');
      $("#search").find("input[type=text]").val('');
      $(".url-container").slideDown('medium');
    });
  });  
  // Select All
  $(document).on('click','#selectall',function(e) {
    e.preventDefault();   
    if($(this).find(".fa-check-square").length > 0){
      $(this).html('<i class="fa fa-minus-square"></i>');
    }else{
      $(this).html('<i class="fa fa-check-square"></i>');
    }
    $('input').iCheck('toggle');
  }); 
  /**
   * Delete All
   */
  $(document).on('click','#deleteall',function(e) {
    e.preventDefault();
    if($(".url-container input[type=checkbox]:checked").length < 1){
      return $(".return-ajax").html('<div class="alert alert-info" style="color:#fff">You must select at least 1 url.</div><br>').fadeIn();
    }    
    $('form#delete-all-urls').attr("action", appurl+"/user/delete");
    $('form#delete-all-urls').submit();
  });  
  /**
   * Active Menu
   **/
  var path = location.pathname.substring(1);  
  if (path) {
    $('.nav-sidebar a').removeClass("active");
    $('.nav-sidebar a[href$="' + path + '"]').addClass('active'); 
  }   
  // Alert Modal
  $(document).on("click", ".delete", function(e){
    e.preventDefault();
    $(this).modal();    
  });
  /**
   * OnClick Select
   **/
   $(".onclick-select").on('click', function(){
    $(this).select();
   })
  /**
   * Show Languages
   **/
  $("#show-language").click(function(e){
    e.preventDefault();
    $(".langs").fadeToggle();
  });
  if($().chosen) {
    $('select.filter').chosen().change(function(e,v){
        var href=document.URL.split("?")[0].split("#")[0];
        window.location=href+"?"+$(this).attr("data-key")+"="+v.selected;
    });   
  }
  $(".tooltip").tooltip();
  // Load all
  loadall();

  function format_date(time){
    var d=new Date(time);
    var list=new Array();
    list[0]="January";list[1]="February";list[2]="March";list[3]="April";list[4]="May";list[5]="June";list[6]="July";list[7]="August";list[8]="September";list[9]="October";list[10]="November";list[11]="December";       
    var month = list[d.getMonth()];
    return d.getDate()+" "+ month +", "+d.getFullYear();
  }  
  // Charts
  if($(".chart").length > 0){
    function showTooltip(x, y, c, d) {
      $('<div id="tooltip" class="chart-tip"><strong>' + c + '</strong><br>'+format_date(d)+'</div>').css( {
          position: 'absolute',
          display: 'none',
          top: y - 40,
          left: x - 30,
          color: '#fff',
          opacity: 0.80
      }).appendTo("body").fadeIn(200);
    }

    var previousPoint = null;
    var previousSeries = null;
    $(".chart").bind("plothover", function (event, pos, item) {
      if(item){
        if(previousSeries != item.seriesIndex || previousPoint != item.dataIndex){
          previousPoint = item.dataIndex;
          previousSeries = item.seriesIndex; 
          $("#tooltip").remove();
          showTooltip(item.pageX, item.pageY, item.datapoint[1]+" Clicks", item.datapoint[0]);          
        }                      
      }
    });     
  }  
  if(typeof Clipboard == "function"){
    new Clipboard('.copy');  
  }  

  $(document).on("click", ".copy", function(e){
    e.preventDefault();  
    $(this).text(lang.copied);
    $(this).prev("a").addClass("float-away");
    setTimeout(function() {
      $("a").removeClass('float-away');
    }, 400);    
  });  

  $(".stripe-button-el span").on("click", function(e){
    
    $(".form-group").removeClass("has-danger");
    
    var $error = 0;

    var $name = $("#name");
    if ($name == "" || $name.val().length < 2) {
        $name.parents(".form-group").addClass("has-danger");
        $error = 1;
    }    

    var $address = $("#address");
    if ($address == "" || $address.val().length < 2) {
        $address.parents(".form-group").addClass("has-danger");
        $error = 1;
    }   

    var $city = $("#city");
    if ($city == "" || $city.val().length < 2) {
        $city.parents(".form-group").addClass("has-danger");
        $error = 1;
    }     

    var $state = $("#state");
    if ($state == "" || $state.val().length < 2) {
        $state.parents(".form-group").addClass("has-danger");
        $error = 1;
    } 

    var $zip = $("#zip");
    if ($zip == "" || $zip.val().length < 2) {
        $zip.parents(".form-group").addClass("has-danger");
        $error = 1;
    }                 
    if($error) return false;
  });
  if(typeof cookieconsent == "object"){
    window.cookieconsent.initialise({
      "palette": {
        "popup": {
          "background": "#2148b1"
        },
        "button": {
          "background": "#fff",
          "color": "#2148b1"
        }
      },
      "theme": "classic",
      "position": "bottom-right",
      "content": {
        "message": lang.cookie,
        "dismiss": lang.cookieok,
        "link": lang.cookiemore,
        "href": appurl + "/page/privacy"
      }
    });    
  }
  // Validate forms
  $(".validate").submit(function(e){
    if(validateForm($(this)) == false ) e.preventDefault();
  });
  $(".contact-event").click(function(e) { 
    e.preventDefault(); 
    $(this).hide(); 
    $(".contact-box").fadeIn(); 
  });  
  $(".contact-close").click(function(e){
    e.preventDefault(); 
    $(".contact-box").hide();
    $(".contact-event").fadeIn();
  });
  $(".contact-form").submit(function(e){
    e.preventDefault();
    if(validateForm($(this)) == false ) return false;
    $.ajax({
        type: "POST",
        url: appurl + "/server",
        data: "request=ajax_form&"+$(this).serialize()+"&token="+token,        
        success: function (response) { 
          $(".contact-box").hide();
          $(".contact-event").fadeIn();
          $(".contact-form").trigger("reset");
          let style = $(".contact-event i").attr("style");
          $(".contact-event i").removeClass("fa-question").addClass("fa-check").attr("style", "background-color:#82e26f;color:#fff");
          setTimeout(function(){
            $(".contact-event i").removeClass("fa-check").addClass("fa-question").attr("style", style);
          }, 5000);
        }
    }); 
  });  
  var poll_max = 10;
  $(".addA").click(function(e){
    e.preventDefault();
    var poll_num = $(".poll-options > .form-group").length;
    if(poll_num == poll_max) return false;
    poll_num++;
    $(".poll-options").append("<div class='form-group'><input type='text' placeholder='Your option "+poll_num+"' class='form-control' name='answer[]' id='answer[]'  placeholder='' data-id='"+poll_num+"'></div>");
    $("ol.poll-answers").append("<li data-id='"+poll_num+"'>Answer "+poll_num+"</li>");
    update_sidebar();
  });
  $(document).on('keyup', '.poll-options input[type=text]', function(){
    let id = $(this).data("id");
    if($(this).val().length <1 || $(this).val().length > 50) return false;
    $("ol.poll-answers li[data-id="+id+"]").text($(this).val());
  });
  $(".poll-form").submit(function(e){
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: appurl + "/server",
        data: "request=ajax_poll&"+$(this).serialize()+"&token="+token,        
        success: function (response) { 
          $(".poll-box").html("<p>Thanks!</p>");
          $(".poll-form").remove();
          let style = $(".contact-event i").attr("style");
          setTimeout(function(){
            $(".poll-overlay").remove();
          }, 2000);
        }
    }); 
  });  
}); // End jQuery Ready
/**
 * iCheck Load Function
 **/
function icheck_reload(){
  if(typeof icheck !== "undefined"){
    var c=icheck;
  }else{
    if($("input[type=checkbox],input[type=radio]").attr("data-class")){
      var c="-"+$("input[type=checkbox],input[type=radio]").attr("data-class");
    }else{
      var c="";
    }    
  }
  if($().iCheck){
    $('input').iCheck({
      checkboxClass: 'icheckbox_flat'+c,
      radioClass: 'iradio_flat'+c
    }); 
  }
}

/**
 * Show Password Field Function
 **/
function show_forgot_password(){
  $("#login_form").slideUp("slow");
  $("#forgot_form").slideDown("slow");  
  return false  
}
/**
 * Custom Radio Box Callback
 **/
function update_sidebar(){
  // Sidebar Height
  if(!is_mobile() && !is_tablet()){
    var h1 = $(".content").height();
    $(".sidebar").height(h1 - 57); 
  }    
}
/**
 * Load zClip
 **/
function zClipload(){
 
}
/**
 * Load Some Functions
 **/
function loadall(){
  zClipload();
  icheck_reload();
  update_sidebar();
  update_autocomplete();
}
// Switch Forms
window.form_switch= function(e){
  if(e == 0){
    $("#multiple").slideUp();
    $("#single").slideDown();
    $(".advanced").fadeIn();    
  }else{
    $("#multiple").slideDown();
    $("#single").slideUp();  
    $(".main-advanced").slideUp();
    $(".advanced").fadeOut();
  }
}
// Auto complete
function update_autocomplete(){
  var parameters = [
    { value: 'utm_source', data: 'utm_source' },
    { value: 'utm_medium', data: 'utm_medium' },
    { value: 'utm_campaign', data: 'utm_campaign' },
    { value: 'utm_term', data: 'utm_term' },
    { value: 'utm_content', data: 'utm_content' },
    { value: 'tag', data: 'tag' },
  ];
  if($().devbridgeAutocomplete){
    $(".autofillparam").devbridgeAutocomplete({
      lookup: parameters
    });
  }
}
// Validate Form 
function validateForm(e){
  
  $(".form-group").removeClass("has-danger");
  $(".form-control-feedback").remove();
  let error = 0;

  e.find("[data-required]").each(function(){

    let type = $(this).attr("type");

    if(type == "email"){
      let regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      if(!regex.test($(this).val())) error = 1;    
    } else {    
      if($(this).val() == "") error = 1;
    }

    if(error == 1) {
      $(this).parents(".form-group").addClass("has-danger");
      $(this).after("<div class='form-control-feedback'>This field is required</div>");
    }

  });
  if(error == 1) {
    return false;
  }  

  return true;
}