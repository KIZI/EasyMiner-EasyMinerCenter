
/*region headerMenu - menu zobrazovaná v hlavičce*/
/**
 * Funkce pro nastavení absolutní pozice pro menu v hlavičce (absolutně vzhledem k dokumentu)
 * @param menuBlock
 * @param menuLink
 * @param documentWidth
 */
var setHeaderMenuPosition = function (menuBlock, menuLink,documentWidth){
  var SPACE_FROM_RIGHT=10;
  var MENU_LINK_ARROW_SIZE=9;
  var menuLinkCenterFromDocumentLeft = menuLink.offset().left+Math.floor(menuLink.outerWidth()/2);
  var menuLinkCenterFromDocumentRight = documentWidth-menuLink.offset().left-Math.floor(menuLink.outerWidth()/2)-SPACE_FROM_RIGHT;
  var menuBlockWidth = menuBlock.outerWidth();
  var menuBlockShiftCenterToLeft=Math.max(0, Math.ceil(menuBlockWidth/2)-menuLinkCenterFromDocumentRight);
  var menuBlockLeft=(menuLinkCenterFromDocumentLeft-menuBlockShiftCenterToLeft-Math.ceil(menuBlockWidth/2));
  menuBlock.css({left:menuBlockLeft,top:(menuLink.offset().top+menuLink.outerHeight()+MENU_LINK_ARROW_SIZE)});
  var menuLinkArrowLeft = Math.floor((menuBlockWidth-MENU_LINK_ARROW_SIZE)/2)+menuBlockShiftCenterToLeft;
  menuBlock.find('div.menuLinkArrow').css({left:menuLinkArrowLeft});
};

/**
 * Připojení událostí nápovědy po načtení stránky
 */
$(document).ready(function($){
  $('.headerMenu').append('<div class="menuLinkArrow"></div>');
  var menuVisible=false;
  var headerUserLink = $('#headerUserLink');
  if (headerUserLink){
    /**
     * Skrytí všech headerMenu při kliknutí na jiný element
     */
    $(document).mousedown(function(event){
      if (!menuVisible){return;}
      var targetElement=$(event.target);
      if (!(targetElement.hasClass('headerMenu') || targetElement.closest('.headerMenu').length || targetElement.closest('.headerLinks').length)){
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }
    });
    /**
     * Událost při kliknutí na odkaz v hlavičce (zobrazení/skrytí menu)
     */
    headerUserLink.click(function(event){
      if ($(this).hasClass('active')){
        //skryjeme menu
        menuVisible=false;
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }else{
        //zobrazíme menu
        $('.headerMenu').hide();
        menuVisible=false;
        $('.headerLinks a').removeClass('active');
        var headerUserMenu=$('#headerUserMenu');
        if (headerUserMenu){
          setHeaderMenuPosition(headerUserMenu,headerUserLink,$(document).width());
          menuVisible=true;
          headerUserMenu.show();
          $(this).addClass('active');
        }
      }
      event.stopPropagation();
      return false;
    });
  }

  var headerAppsLink = $('#headerAppsLink');
  if (headerAppsLink){
    headerAppsLink.click(function(event){
      if ($(this).hasClass('active')){
        //skryjeme menu
        menuVisible=false;
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }else{
        //zobrazíme menu
        menuVisible=false;
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');

        var headerAppsMenu=$('#headerAppsMenu');
        if (headerAppsMenu){
          setHeaderMenuPosition(headerAppsMenu,headerAppsLink,$(document).width());
          menuVisible=true;
          headerAppsMenu.show();
          $(this).addClass('active');
        }
      }
      event.stopPropagation();
      return false;
    });
  }

});

/*endregion headerMenu*/

/*region long running forms*/
/**
 * Připojení událostí pro slow formuláře
 */
$(document).ready(function($){
  //připojení funkce pro odchycení custom eventu afterSubmit
  $('form.slowForm').on("afterSubmit",function(){
    $("body").addClass("slowLoading");
  });
  //TODO add support for ajax forms
  //připojení prvku, který řeší překrytí obsahu spinnerem
  $("body").append('<div id="loadingOverlay"></div>');
});

/*endregion long running forms*/