/**
 * Main scripts for UI of EasyMiner
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Object for management of HeaderMenu
 * @param $ {jQuery}
 * @param options
 * @constructor
 */
var HeaderMenu = function($,options){
  this.menuVisible = false;

  /**
   * @type {{subMenus: Array, links: Array, SPACE_FROM_RIGHT: number, MENU_LINK_ARROW_SIZE: number}}
   */
  this.options = {
    subMenus : ['User','Apps'],
    links : ['Help'],
    SPACE_FROM_RIGHT : 10,
    MENU_LINK_ARROW_SIZE : 9,
    MARGIN_CONTENT: 5
  };

  /**
   * Function for setting of absolute position for menus in header (absolutely to the document)
   * @param {object} menuBlock
   * @param {object} menuLink
   * @private
   */
  this.setSubMenuPosition = function(menuBlock, menuLink){
    var documentWidth = $(document).width();
    var menuLinkCenterFromDocumentLeft = menuLink.offset().left+Math.floor(menuLink.outerWidth()/2);
    var menuLinkCenterFromDocumentRight = documentWidth-menuLink.offset().left-Math.floor(menuLink.outerWidth()/2)-this.options.SPACE_FROM_RIGHT;
    var menuBlockWidth = menuBlock.outerWidth();
    var menuBlockShiftCenterToLeft=Math.max(0, Math.ceil(menuBlockWidth/2)-menuLinkCenterFromDocumentRight);
    var menuBlockLeft=(menuLinkCenterFromDocumentLeft-menuBlockShiftCenterToLeft-Math.ceil(menuBlockWidth/2));
    menuBlock.css({left:menuBlockLeft,top:(
        menuLink.offset().top + menuLink.outerHeight() + this.options.MENU_LINK_ARROW_SIZE + this.options.MARGIN_CONTENT + this.getAdditionalTop()
      )
    });
    var menuLinkArrowLeft = Math.floor((menuBlockWidth-this.options.MENU_LINK_ARROW_SIZE)/2)+menuBlockShiftCenterToLeft;
    menuBlock.find('div.menuLinkArrow').css({left:menuLinkArrowLeft});
  };

  this.getAdditionalTop = function () {
    if ($('header .content')[0].scrollHeight > 100 && window.getSize().x <= 960) {
      return 18;
    } else {
      return 0;
    }
  }

  /**
   * Function for initialization of all submenus (headerMenus)
   * @param {string} menuItem
   * @private
   */
  this.initSubMenu = function(menuItem){
    var linkElement = $('#header'+menuItem+'Link');
    if (linkElement){
      var headerMenu = this;
      linkElement.click(function(event){
        if ($(this).hasClass('active')){
          //hide menu
          headerMenu.menuVisible=false;
          $('.headerMenu').hide();
          $('.headerLinks a').removeClass('active');
        }else{
          //display menu
          $('.headerMenu').hide();
          headerMenu.menuVisible=false;
          $('.headerLinks a').removeClass('active');
          var menuElement=$('#header'+menuItem+'Menu');
          if (menuElement){
            headerMenu.setSubMenuPosition(menuElement, linkElement);
            headerMenu.menuVisible=true;
            menuElement.show();
            $(this).addClass('active');
          }
        }
        event.stopPropagation();
        return false;
      });
    }
  };

  /**
   * Function for initialization of all links without submenus
   * @param {string} menuItem
   * @private
   */
  this.initLink = function(menuItem){
    var linkElement = $('#header'+menuItem+'Link');
    if (linkElement){
      linkElement.click(function(){
        //hide menu
        this.menuVisible=false;
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }.bind(this));
    }
  };

  /**
   * @private
   * @param options
   */
  this.init = function(options){
    $.extend(this.options, options);
    /*region append menuLinkArrow to all headerMenus*/
    $('.headerMenu').append('<div class="menuLinkArrow"></div>');
    /*endregion append menuLinkArrow to all headerMenus*/
    /*region hidding of all headerMenus after clicking on another menu element*/
    $(document).mousedown(function(event){
      if (!this.menuVisible){return;}
      var targetElement=$(event.target);
      if (!(targetElement.hasClass('headerMenu') || targetElement.closest('.headerMenu').length || targetElement.closest('.headerLinks').length)){
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }
    }.bind(this));
    /*endregion hidding of all headerMenus after clicking on another menu element*/
    /*region initialize all submenus*/
    $.each(this.options.subMenus,function(index, value){
      this.initSubMenu(value);
    }.bind(this));
    /*endregion*/
    /*region initialize help*/
    $.each(this.options.links,function(index, value){
      this.initLink(value);
    }.bind(this));
    /*endregion*/
  };

  /*initialize object*/
  this.init($,options);
};


/*region headerMenu - menu displayed in the header*/
/**
 * Append the events for EasyMiner-Help after the page load
 */
$(document).ready(function($){
  var headerMenu = new HeaderMenu($);
  console.log($('#show-main-header-menu'))
  $('#show-main-header-menu').click(function () {
    var headerLinks = $('.headerLinks');
    var header = $('#applicationMainTitle')
    if (headerLinks.hasClass('show')) {
      headerLinks.removeClass('show');
      headerMenu.menuVisible=false;
      $('.headerMenu').hide();
      $('.headerLinks a').removeClass('active');
    } else {
      headerLinks.addClass('show');
    }
    if (header.hasClass('small')) {
      header.removeClass('small');
    } else {  
      header.addClass('small');
    }
  })
  
  $('main').click(function () {
    var headerLinks = $('.headerLinks');
    var header = $('#applicationMainTitle')
    if (headerLinks.hasClass('show')) {
      headerLinks.removeClass('show');
      headerMenu.menuVisible=false;
      $('.headerMenu').hide();
      $('.headerLinks a').removeClass('active');
    } else {
      headerLinks.addClass('show');
    }
    if (header.hasClass('small')) {
      header.removeClass('small');
    } else {  
      header.addClass('small');
    }
  })
});

/*endregion headerMenu*/

/*region long running forms*/
/**
 * Attach events for slow forms
 */
$(document).ready(function($){
  //append the element for the spinner overlay
  $("body").append('<div id="loadingOverlay"></div>');
  //append the function for custom event afterSubmit
  $('form:not(.ajax)').on("afterSubmit",function(){
    if(!$(this).hasClass('ajax')){
      $("body").addClass("slowLoading");
    }
  });
  $('a.slowLoading').click(function(){
    $("body").addClass("slowLoading");
  });
});

/*endregion long running forms*/