/**
 * Funkce pro nastavení absolutní pozice pro menu v hlavičce (absolutně vzhledem k dokumentu)
 * @param menuBlock
 * @param menuLink
 * @param documentWidth
 */
function setHeaderMenuPosition(menuBlock, menuLink,documentWidth){
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
}


$(document).ready(function($){
  //region prepareHeaderMenus
  $('.headerMenu').append('<div class="menuLinkArrow"></div>');

  var headerUserLink = $('#headerUserLink');
  if (headerUserLink){
    headerUserLink.click(function(event){
      if ($(this).hasClass('active')){
        //skryjeme menu
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }else{
        //zobrazíme menu
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');

        var headerUserMenu=$('#headerUserMenu');
        if (headerUserMenu){
          setHeaderMenuPosition(headerUserMenu,headerUserLink,$(document).width());
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
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');
      }else{
        //zobrazíme menu
        $('.headerMenu').hide();
        $('.headerLinks a').removeClass('active');

        var headerAppsMenu=$('#headerAppsMenu');
        if (headerAppsMenu){
          setHeaderMenuPosition(headerAppsMenu,headerAppsLink,$(document).width());
          headerAppsMenu.show();
          $(this).addClass('active');
        }
      }
      event.stopPropagation();
      return false;
    });
  }

});
