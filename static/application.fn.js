/**
 *  Premium URL Shortener jQuery Custom Functions - Don't edit anything below!
 *  Copyright @KBRmedia - All rights Reserved 
 */
(function($){
  // Sticky
  $.fn.extend({
    sticky: function(a) {
      var b = {
        TopMargin: '0'
      }
      var e=$(this);
      if(!e.length)  return false;
      var s = $.extend(b, a);
      pos=e.position();
      $(window).scroll(function(){   
        if(window.pageYOffset>=pos.top){              
            e.css({
                position: 'fixed',
                top: s.TopMargin,
                left: pos.left,
                margin: 0
            });                                   
        }else{
            e.attr('style','');           
        }   
      });                   
    }       
  });
  // Tooltip
  $.fn.extend({
    tooltip: function(a){   
      $("#tooltip").remove();
      $(this).click(function(){
        $("#tooltip").remove();
      });
      $(this).hover(function(){
        var d=$(this).attr("data-content");              
        var pos=$(this).offset();
        var x=pos.left + $(this).width() + 20;
        var y=pos.top;
        $("body").append("<span id='tooltip' style='position:absolute;top:"+y+"px;left:"+x+"px'>"+d+"</span>");
      },function(){
        $("#tooltip").remove();
      });
    }
  });
 // Smooth Scroll
  $.fn.extend({ 
    smoothscroll: function(settings) {
      var defaults = {
          holder: 0
      };
      var s = $.extend(defaults, settings);      
      $(this).click(function(e){
        e.preventDefault();
        if(!s.holder){
          var href=$(this).attr("href");
        }else{
          var href=s.holder;
        }
        var pos=$(href).position(); 
        var css=$(this).attr('class');
            $("."+css).removeClass("active");
            $('body,html').animate({ scrollTop: pos.top-30 });               
      });
    }        
  });    
  // Modal
  $.fn.extend({
    modal: function(settings) {
      var defaults = {
          confimation: 0,
          title:$(this).attr("title"),
          content:$(this).attr("data-content"),
          link:$(this).attr("href")
      };
      var s = $.extend(defaults, settings);
      if(!s.content){
          s.content="Note that this action is permanent. Once you click proceed, you <strong>may not undo</strong> this. Click anywhere outside this modal or click <a href='#close' class='close-modal'>close</a> to close this.";
      }
      if(!s.title){
        s.title="Are you sure you want to proceed?";
      }
      if(s.confimation){
          var proceed="";
      }else{
          var proceed="<a href='" + s.link + "' class='btn btn-success btn-xs'>Proceed</a> <a href='#' class='btn btn-danger btn-xs close-modal'>Cancel</a>";
      }
      $.fn.modal_destroy=function(){
        $("#modal-shadow").fadeOut('normal',function(){
          $(this).remove();
        });
        $("#modal-alert").fadeOut('normal',function(){
          $(this).remove();
        }); 
        return;       
      }
      if($("#modal-alert").length>0) $(document).modal_destroy();
      $("body").prepend('<div id="modal-alert"></div><div id="modal-shadow"></div>');
      $("#modal-shadow").css("height",$(document).height()).hide();
      $("#modal-shadow").show();
      var left=($(document).width() - $("#modal-alert").width())*0.5;
      var top=80;
      $("#modal-alert").css({"top":top,"left":left}).hide();
      $("#modal-alert").fadeIn();
      $("#modal-alert").html("<div class='title'>"+s.title+" <a href='#' class='btn btn-danger btn-xs pull-right close-modal'>Close</a></div><p>"+ s.content +"</p>"+proceed);
      $(document).on('click',".close-modal,#modal-shadow",function(e) {
      	e.preventDefault();
        $(document).modal_destroy();
      });
      var pos=$('#modal-alert').position(); 
      $(this).smoothscroll({holder:'#modal-alert'});           
      $(document).keyup(function(e) {
        if (e.keyCode == 27) {       
        	e.preventDefault();
          $(document).modal_destroy();
        }   
      });                 
    }    
  });  
})(jQuery);    
function is_mobile(){
    if($(document).width()<550) return true;
    return false;
}
function is_tablet(){
    if($(document).width()>=550 && $(document).width()<980) return true;
    return false;
}