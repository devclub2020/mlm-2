﻿$(window).load(function() {
$('#slider').orbit({
     animation: 'horizontal-slide',             // fade, horizontal-slide, vertical-slide, horizontal-push
     animationSpeed: 800,                // how fast animtions are
     timer: true, 			 // true or false to have the timer
     advanceSpeed: 5000, 		 // if timer is enabled, time between transitions 
     pauseOnHover: true, 		 // if you hover pauses the slider
     startClockOnMouseOut: true, 	 // if clock should start on MouseOut
     startClockOnMouseOutAfter: 300, 	 // how long after MouseOut should the timer start again
     directionalNav: true, 		 // manual advancing directional navs
     captions: true, 			 // do you want captions?
     captionAnimation: 'slideOpen', 		 // fade, slideOpen, none
     captionAnimationSpeed: 800, 	 // if so how quickly should they animate in
     bullets: false,			 // true or false to activate the bullet navigation
     bulletThumbs: false,		 // thumbnails for the bullets
     bulletThumbLocation: '',		 // location from this file where thumbs will be
     //afterSlideChange: function(){} 	 // empty function 
});
});

$(document).ready(function() {
$(function() { $("form.openid:eq(0)").openid(); });
});


$(document).ready(function() {
$('#imguploader').modal({
  show: false
});
});
