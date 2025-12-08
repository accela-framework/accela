<?php

namespace Accela;

class PageCommon extends Page {
  public string $style;

  public function __construct(string $path, Accela $accela){
    parent::__construct("/../common", $accela);
  }

  public function initialize(string $path, string $content, string|null $staticPath=null): void {
    $this->path = $path;
    $this->head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $this->head = preg_replace("@[ \t]+<@", "<", $this->head);

    $this->style = "";
    if(preg_match("@<style>.+?</style>@s", $content)){
      $this->style = preg_replace("@^.*<style>[\s\t\n]*(.+?)[\s\t\n]*</style>.*$@s", '$1', $content);
    }

    $this->body = "";

    $this->props = $this->accela->pageProps->get($path);

    $this->head = $this->evaluateServerComponent($this->head, $this->props);
  }

  public function getCss(){
    return trim($this->style);
  }
}
