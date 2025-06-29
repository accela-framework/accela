<?php

namespace Accela;

// lang
define("HTML_LANG", "ja");

// for sitemap.xml
define("ROOT_URL", "https://example.com");

// seconds for Cache-Control
// define("SERVER_LOAD_INTERVAL", 60);

// PageProps
Accela::pageProps("/", function(){
  return [
    "name" => "Accela",
  ];
});
