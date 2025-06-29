<?php

namespace Accela;

class PageCommon extends Page {
  public string $style;

  public function __construct(){
    parent::__construct("/../common");
  }

  public function initialize(string $path, string $content, string|null $static_path=null): void {
    $this->path = $path;
    $this->head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $this->head = preg_replace("@[ \t]+<@", "<", $this->head);

    $this->style = "";
    if(preg_match("@<style>.+?</style>@s", $content)){
      $this->style = preg_replace("@^.*<style>[\s\t\n]*(.+?)[\s\t\n]*</style>.*$@s", '$1', $content);
    }

    $this->body = "";

    $this->props = PageProps::get($path);

    $this->head = $this->evaluateServerComponent($this->head, $this->props);
  }

  public function getCss(){
    return trim($this->style);
  }

  public static function instance(): PageCommon {
    static $instance;
    if(!$instance) $instance = new self();
    return $instance;
  }
}
