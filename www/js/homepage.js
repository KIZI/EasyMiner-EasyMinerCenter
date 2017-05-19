/**
 * JavaScript for the animation on homepage
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Function for change of the slogan text
 * @param parentBlock
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