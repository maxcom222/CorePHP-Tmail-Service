/*
 * ====================================================================================
 * ----------------------------------------------------------------------------------
 *  This plugin is built for the Premium URL Shortener that can bought from 
 *  CodeCanyon. This will not work with any other URL shorteners and I will not 
 *  implement it to other scripts. This is distributed free of charge with the script.
 *
 *  Premium URL Shortener  - https://gempixel.com/buy/short
 *  Copyright (c) KBRmedia - https://gempixel.com
 *  @since v1.0
 * ----------------------------------------------------------------------------------
 * ====================================================================================
 */ 

var url = "__URL__";
var error = 0;

if(selector === undefined){
  var selector = "a";
}

if(url === null){
    console.log('Please set the url to the api of the url shortener script.');
    error = 1;
}
if(key === undefined){
    console.log('Please set your API key.');
    error = 1;              
}

if(error == 0){  
  var elements = document.querySelectorAll(selector);
  Array.prototype.forEach.call(elements, function(el){
    var skip = 0;
    if(typeof exclude !== 'undefined'){
      if(exclude.indexOf(getHost(el.getAttribute('href'))) > -1) skip = 1;
    }
    if(skip === 0){
      GemPixel(url+'/api?key='+key+'&url='+el.getAttribute('href'), function(response) {
        if(response.error=='0'){
          el.setAttribute('href',response.short);
        }else{
          console.log(response.msg);
        }        
      });      
    }
  });
}
function GemPixel(url, callback) {
  var callbackName = 'GemPixel_' + Math.round(100000 * Math.random());
  window[callbackName] = function(data) {
      delete window[callbackName];
      document.body.removeChild(script);
      callback(data);
  };
  var script = document.createElement('script');
  script.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'callback=' + callbackName;
  document.body.appendChild(script);
}
function getHost(url) {
  var hostname;
  if (url.indexOf("://") > -1) {
    hostname = url.split('/')[2];
  }else {
    hostname = url.split('/')[0];
  }
  hostname = hostname.split(':')[0];
  hostname = hostname.split('?')[0];
  return hostname.replace("www","");
}