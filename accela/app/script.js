ACCELA.initPage = function(){
  document.querySelector("body").classList.add("show")
};

ACCELA.movePage = function(page, move){
  move();
};

ACCELA.changePageContent = function(body, pageContent){
  body.innerHTML = "";
  body.appendChild(pageContent);
};


// module
ACCELA.modules.greeting = (body) => {
  const name = body.getAttribute("data-name");
  body.innerText = `Hello ${name}!`;
};
