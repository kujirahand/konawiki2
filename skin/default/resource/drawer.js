// draw.js for konawiki2 (kujirahand)
$(function(){

  const btn = $('#btn-gnavi');
  const win = $("#closeWindow");
  const nav = $("nav.global-navi");
  
  function toggleMenu() {
    if (btn.hasClass("open")) {
      closeMenu();
    } else {
      openMenu();
    }
  }
  
  function openMenu() {
    console.log('open');
    btn.addClass("open");
    execMenu(0);
    win.css('left', '0px');
    win.css('top', '0px');
    win.css('height', '100%');
    win.css('width', '100%');
    win.fadeIn();
  }
  
  function closeMenu() {
    console.log('close');
    btn.removeClass("open");
    execMenu(-300);
    win.fadeOut();
  }
  
  function execMenu(pos) {
    nav.stop().animate({
        right: pos
    }, 200);
  }
  btn.on("click", toggleMenu);
  win.on("click", closeMenu);
  
});


