
if(typeof blockAdBlock === 'undefined') {
	engageBlock();
} else {
	blockAdBlock.onDetected(engageBlock);
}

blockAdBlock.setOption({
	checkOnLoad: true,
	resetOnEnd: false
});

function engageBlock() {
  $("body").html('');
  $("body").prepend('<div id="detect-app"><h3>'+detect.on+'<span>'+detect.detail+'</span></h3></div>');
  $("#detect-app").css("height", $(document).height()).hide();
  $("#detect-app").fadeIn();
}