<?php

namespace Accela;

class PageCommon extends Page {
  public string $style;

  public function __construct(string $path, Accela $accela){
    parent::__construct("/../common", $accela);
  }

  public function initialize(string $path, string $content, string|null $staticPath=null): void {
    $this->path = $path;

    $head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $head = preg_replace("@[ \t]+<@", "<", $head);

    $this->style = "";
    if(preg_match("@<style>.+?</style>@s", $content)){
      $this->style = preg_replace("@^.*<style>[\s\t\n]*(.+?)[\s\t\n]*</style>.*$@s", '$1', $content);
    }

    $this->props = $this->accela->pageProps->get($path);

    $head = $this->evaluateServerComponent($head, $this->props);

    // create DOM
    $this->headDom = createDom($head);
    $this->metaDom = createDom('');
    $this->bodyDom = createDom('');
  }

  public function getCss(){
    return trim($this->style);
  }
}