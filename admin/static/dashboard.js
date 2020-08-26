/**
 * 
 **/
 $(document).ready(function(){
  if($('[data-toggle="datetimepicker"]').length > 0){
    $('[data-toggle="datetimepicker"]').datepicker({
      autoPick: true,
      format: "yyyy-mm-dd"
    });    
  }  
  $(".sidebar  li > a").click(function(){
      if($(this).hasClass("active")){
          return false;
      }
      var link= $(this).attr("href");
      if(link ==""){
       $(".sidebar li").removeClass("active");
       $(".sidebar li").find("div").slideUp("slow");
       $(this).parent("li").find('div').slideToggle("slow");
       $(this).parent("li").addClass('active');
        return false;
      }     
        
  });   
  /**
   * Easy Tabs
   **/
    $(".tabbed").hide();
    $(".tabbed").filter(":first").fadeIn();
    $(".tabs a").click(function(e){
      if($(this).attr("data-link")){
        return;
      }
      e.preventDefault();
      var id=$(this).attr("href");
      $(".tabs li").removeClass("active");
      $(this).parent("li").addClass("active");
      $(".tabbed").hide();
      $(id).fadeIn();
      if(!is_mobile() && !is_tablet()){
        update_sidebar();
        $(".sub-sidebar").height($(id).height()+100);
      }
    });  
  /**
   * Add & Delete Location Field
   */
    var html=$(".country").html();
    $(".add_geo").click(function(){
      $('#geo').append("<div class='form-group'>"+html+"</div><p><a href='#' class='btn btn-danger btn-xs delete_geo'>Delete</a></p>");
      update_sidebar();
      $("select").chosen({disable_search_threshold: 5});
      return false;
    }); 
    $(document).on('click',".delete_geo",function(e){
      e.preventDefault();
      var t=$(this);
      $(this).parent('p').prev('div.form-group').slideUp('slow',function(){
        $(this).remove();
        t.parent('p').remove();
      });
      return false;
    });    
  /**
   * Active Menu
   **/
  var path = location.pathname.substring(1);
  if (path) {
    $('.nav-sidebar li').removeClass("active");
    $('.nav-sidebar li a[href$="' + path + '"]').addClass('active'); 
    $('.nav-sidebar li a[href$="' + path + '"]').parents("li").addClass('active'); 
  }    		
  // Chosen
  $("select").chosen({disable_search_threshold: 5});
  /**
   * Update Filter + Theme
   **/
  $('select#filter').chosen().change(function(e,v){
      var href=document.URL.split("?")[0].split("#")[0];
      window.location=href+"?filter="+v.selected;
  }); 
  $('select#perpage').chosen().change(function(e,v){
      var href=document.URL.split("?")[0].split("#")[0];
      window.location=href+"?perpage="+v.selected;
  });   
  $('select#theme_files').chosen().change(function(e,v){
      window.location=appurl+"/themes/editor/"+$("select#theme_files").val();
  });  
  /**
   * Update Themes
   **/
  $(".themes-style li a").click(function(e){
    e.preventDefault();
    var c=$(this).attr("data-class");
    $(".themes-style li a").removeClass("current");
    $(this).addClass("current");   
    $("#theme_value").val(c);
  });    
  /**
   * Delete Alert
   **/
  $(".delete").click(function(e){
    e.preventDefault();
    $(this).modal();
    return false;
  });   
  // Remove logo
  $("#remove_logo").click(function(e){
    e.preventDefault();
    $("#setting-form").append("<input type='hidden' name='remove_logo' value='1'>");
    $(this).text("Logo will be removed upon submission");
  });  
  $("#remove_favicon").click(function(e){
    e.preventDefault();
    $("#setting-form").append("<input type='hidden' name='remove_favicon' value='1'>");
    $(this).text("Favicon will be removed upon submission");
  });    
  // Remove Alert
 $("div.alert").click(function(){
    $(this).fadeOut();
 });   
 //Back to top
  $(window).scroll(function(){   
    if(window.pageYOffset>300){
      $("#back-to-top").fadeIn('slow');
    }else{
      $("#back-to-top").fadeOut('slow');
    }
  });
  $("a#back-to-top, a.scroll").smoothscroll();  

  // Check All
  $('#check-all').on('click', function(e) {
    var form=$(this).parents("form");
    $("p.cta-hide").fadeIn();
    if(form.find('.data-delete-check').prop('checked')){
      $(this).text("Select All");
      $(this).prop('checked', false);
      form.find('.data-delete-check').prop('checked', false);
    }else{
      $(this).text("Unselect All");
      $(this).prop('checked', true);
      form.find('.data-delete-check').prop('checked', true);
    }    
  });     
  /**
   * Custom Radio Box
   */ 
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
  // Charts
  if($(".chart").length > 0){
    function showTooltip(x, y, c) {
      $('<div id="tooltip" class="chart-tip">' + c + '</div>').css( {
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
          showTooltip(item.pageX, item.pageY, item.datapoint[1]+" "+item.series["label"]);          
          $("#tooltip").addClass(item.series["label"].toLowerCase());             
        }                      
      }
    });     
  }
  update_sidebar();
});
function update_sidebar(){
  // Sidebar Height
  if(!is_mobile() && !is_tablet()){
    $(".sub-sidebar").height($('.tabbed').height()+100);
    var h1=$(document).height()-100;
    $(".sidebar").height(h1); 
  }
}
window.solvemedia = function(e){
  if(e==2){    
    $(".solvemedia").slideDown();
  }else{
    $(".solvemedia").slideUp();
  }
  update_sidebar();
}