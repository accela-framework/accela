ACCELA.modules.redirect = (body) => {
  setTimeout(() => ACCELA._movePage(body.dataset.href), 0);
};
