/**
 * JavaScript pro animaci na homepage
 * Created by Stanislav on 29. 7. 2015.
 */

var nextSlogan = function(parentBlock){
  var visibleBlock=parentBlock.children('div.visible');
  if (visibleBlock){
    visibleBlock.removeClass('visible');
    var nextElement = visibleBlock.next();
    if (nextElement.length>0){
      nextElement.addClass('visible');
    }else{
      var firstSlogan=parentBlock.children('div').first();
      firstSlogan.addClass('visible');
    }
  }
};

$(document).ready(function(){
  var sloganSection = $('#slogan');
  if (sloganSection){
    setInterval(function(){nextSlogan(sloganSection);},2500);
  }
});