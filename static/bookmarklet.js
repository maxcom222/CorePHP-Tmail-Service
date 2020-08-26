(function(){
  // the minimum version of jQuery we want
  var v = "1.5";
  // check prior inclusion and version
  if (window.jQuery === undefined || window.jQuery.fn.jquery < v) {
    var done = false;
    var script = document.createElement("script");
    script.src = "http://ajax.googleapis.com/ajax/libs/jquery/" + v + "/jquery.min.js";
    script.onload = script.onreadystatechange = function(){
      if (!done && (!this.readyState || this.readyState == "loaded" || this.readyState == "complete")) {
        done = true;
        gem_shorten();
      }
    };
    document.getElementsByTagName("head")[0].appendChild(script);
  } else {
    gem_shorten();
  }
  
  function gem_shorten() {
    (window.myBookmarklet = function() {
     var appurl=jQuery("#gem_bookmarklet").attr("data-url");
     var token=jQuery("#gem_bookmarklet").attr("data-token");
      // HTML Template
      var html='<div id="gempixel-bookmarlet" style="z-index:99999 !important;box-shadow:0 5px 10px rgba(0,0,0,0.15) !important;font-family:arial !important;font-size:13px !important;background:#fff !important;border:2px solid #eee !important;position:fixed !important;border-radius:3px !important;color:#000 !important; padding-bottom: 5px !important;"><h2 style="border-bottom:1px solid #eee !important;margin:0 !important ;padding:10px !important;font-size:14px !important;color:#000 !important;">URL Shortener<a style="color:#000 !important;font-size:11px !important;text-align:right !important;text-decoration:none !important;float:right !important;margin-top:2px !important;" id="close" href="#close">(Close)</a></h2><form style="padding:10px !important;"><label for="gem-url">Short URL Created</label><input style="border-radius:3px !important;margin-top:10px !important;background:#fff !important;border:1px solid #32a0ee !important;width:250px !important;display:block !important;padding:5px 8px !important;" type="text" name="url" id="gem-url" value=""></form><a href="#fb" id="gem-twitter" style="display:inline-block !important; width:85px !important; padding: 8px !important; margin: 2px !important;margin-left:5px !important;text-decoration:none !important;color:#fff !important;background:#409DD5 !important;text-align:center !important;border-radius:2px;margin-bottom:10px;"  target="_blank">Tweet</a> <a href="#tw" id="gem-facebook"style="display:inline-block !important; width:85px !important; padding: 8px !important; margin: 2px !important;text-decoration:none !important;color:#fff !important;background:#3B5998 !important;text-align:center !important;border-radius:2px;" target="_blank">Share</a></div>';
      //Append HTML
      jQuery("body").append(html);
      //Adjust CSS
      jQuery("#gempixel-bookmarlet").css({top:'20px',left:((jQuery(document).width() - jQuery("#gempixel-bookmarlet").width())*0.5)});
      //Show Box
      jQuery("#gempixel-bookmarlet").slideDown('slow');
      //Close and Remove Box
      jQuery("#gempixel-bookmarlet #close").click(function(e){
        e.preventDefault();
        jQuery("#gempixel-bookmarlet").remove();
      });
      jQuery.getJSON(appurl+"/?&bookmark=true&callback=?",
      {
        url: document.URL,
        token: token
      },
      function(r) {
       if(r.error=='0'){
        jQuery("#gempixel-bookmarlet #gem-url").val(r.short).select();
        jQuery("#gem-facebook").attr("href","https://www.facebook.com/sharer.php?u="+r.short);
        jQuery("#gem-twitter").attr("href","https://twitter.com/share?url="+r.short);
       }else{
        jQuery("#gempixel-bookmarlet #gem-url").val(r.msg);
       }      
      });  
    })();
  }
})();