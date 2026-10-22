(function () {
  "use strict";
  var menuButton=document.querySelector(".mobile-menu-btn");
  var menu=document.getElementById("eco-menu");
  if(menuButton && menu){
    menuButton.addEventListener("click",function(){
      var open=menu.classList.toggle("is-open");
      menuButton.setAttribute("aria-expanded",open?"true":"false");
      menuButton.textContent=open?"×":"☰";
    });
    menu.querySelectorAll("a").forEach(function(a){a.addEventListener("click",function(){menu.classList.remove("is-open");menuButton.setAttribute("aria-expanded","false");});});
  }
  var buttons=[].slice.call(document.querySelectorAll("[data-filter]"));
  var input=document.getElementById("eco-product-search");
  var cards=[].slice.call(document.querySelectorAll(".catalog .product-card"));
  var empty=document.querySelector(".empty-state");
  var active="all";
  function normal(s){return (s||"").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/đ/g,"d");}
  function apply(){
    var query=normal(input?input.value:"");
    var shown=0;
    cards.forEach(function(card){
      var category=card.dataset.category||"";
      var text=normal(card.textContent);
      var match=(active==="all" || category===active) && (!query || text.indexOf(query)!==-1);
      card.hidden=!match;
      card.style.display=match?"":"none";
      if(match)shown++;
    });
    if(empty)empty.style.display=shown?"none":"block";
  }
  buttons.forEach(function(button){
    button.addEventListener("click",function(){
      active=button.dataset.filter;
      buttons.forEach(function(b){b.classList.toggle("is-active",b===button);b.setAttribute("aria-pressed",b===button?"true":"false");});
      apply();
    });
  });
  if(input)input.addEventListener("input",apply);
})();
