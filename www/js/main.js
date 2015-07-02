/**
 * Object for management of HeaderMenu
 * @param options
 * @constructor
 */
var HeaderMenu = function(options){
  this.menuVisible = false;
  /**
   * @type {{subMenus: Array, links: Array, SPACE_FROM_RIGHT: number, MENU_LINK_ARROW_SIZE: number}}
   */
  this.options = {
    subMenus : ['User','Apps'],
    links : ['Help'],
    SPACE_FROM_RIGHT : 10,
    MENU_LINK_ARROW_SIZE : 9
  };

  /**
   *
   * Funkce pro nastavení absolutní pozice pro menu v hlavičce (absolutně vzhledem k dokumentu)
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
    menuBlock.css({left:menuBlockLeft,top:(menuLink.offset().top+menuLink.outerHeight()+this.options.MENU_LINK_ARROW_SIZE)});
    var menuLinkArrowLeft = Math.floor((menuBlockWidth-this.options.MENU_LINK_ARROW_SIZE)/2)+menuBlockShiftCenterToLeft;
    menuBlock.find('div.menuLinkArrow').css({left:menuLinkArrowLeft});
  };

  /**
   * Funkce pro inicializaci jednotlivých subMenu
   * @param {string} menuItem
   * @private
   */
  this.initSubMenu = function(menuItem){
    var linkElement = $('#header'+menuItem+'Link');
    if (linkElement){
      var headerMenu = this;
      linkElement.click(function(event){
        if ($(this).hasClass('active')){
          //skryjeme menu
          headerMenu.menuVisible=false;
          $('.headerMenu').hide();
          $('.headerLinks a').removeClass('active');
        }else{
          //zobrazíme menu
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
   * Funkce pro inicializaci jednotlivých odkazů bez submenu
   * @param {string} menuItem
   * @private
   */
  this.initLink = function(menuItem){
    var linkElement = $('#header'+menuItem+'Link');
    if (linkElement){
      linkElement.click(function(){
        //skryjeme menu
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
    /*region připojení menuLinkArrow k jednotlivým submenu*/
    $('.headerMenu').append('<div class="menuLinkArrow"></div>');
    /*endregion*/
    /*region skrytí všech headerMenu při kliknutí na jiný element */
    $(document).mousedown(function(event){
      if (!this.menuVisible){return;}
      var targetElement=$(event.target);
      if (!(targetElement.hasClass('headerMenu') || targetElement.closest('.headerMenu').length || targetElement.closest('.headerLinks').length)){
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }
    }.bind(this));
    /*endregion*/
    /*region inicializace jednotlivých submenu*/
    $.each(this.options.subMenus,function(index, value){
      this.initSubMenu(value);
    }.bind(this));
    /*endregion*/
    /*region inicializace nápovědy*/
    $.each(this.options.links,function(index, value){
      this.initLink(value);
    }.bind(this));
    /*endregion*/
  };

  /*initialize object*/
  this.init($,options);
};


/*region headerMenu - menu zobrazovaná v hlavičce*/
/**
 * Připojení událostí nápovědy po načtení stránky
 */
$(document).ready(function($){
  new HeaderMenu($);
});

/*endregion headerMenu*/

/*region long running forms*/
/**
 * Připojení událostí pro slow formuláře
 */
$(document).ready(function($){
  //připojení prvku, který řeší překrytí obsahu spinnerem
  $("body").append('<div id="loadingOverlay"></div>');
  //připojení funkce pro odchycení custom eventu afterSubmit
  $('form:not(.ajax)').on("afterSubmit",function(){
    $("body").addClass("slowLoading");
  });
});

/*endregion long running forms*/