<?php

namespace Accela;

class ServerComponentNotFoundError extends \Exception {
  public string $component_name;
}

class ServerComponentDomainNotFoundError extends \Exception {
  public string $domain_name;
}

class ServerComponent {
  public static array $domains = [];
  public string $path;

  public function __construct(){
  }

  public static function load(string $component_name): ServerComponent {
    $sc = new ServerComponent();

    $domain = "app";
    if(strpos($component_name, ":") !== FALSE){
      list($domain, $component_name) = explode(":", $component_name);
    }

    if(!isset(self::$domains[$domain])){
      $e = new ServerComponentDomainNotFoundError("server component domain '{$domain}' not founds.");
      $e->domain_name = $domain;
      throw $e;
    }

    $sc->path = rtrim(self::$domains[$domain], "/") . "/{$component_name}.php";

    if(!is_file($sc->path)){
      $e = new ServerComponentNotFoundError("'{$component_name}' server component not founds.");
      $e->component_name = $component_name;
      throw $e;
    }

    return $sc;
  }

  public function evaluate(array $props, string $content): string {
    $sc = $this;

    return capture(function()use($sc, $props, $content): void {
      include $sc->path;
    });
  }

  public static function registerDomain(string $domain, string $path){
    self::$domains[$domain] = $path;
  }
}
