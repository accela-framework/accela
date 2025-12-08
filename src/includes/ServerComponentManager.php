<?php

namespace Accela;

class ServerComponentNotFoundError extends \Exception {
  public string $componentName;
}

class ServerComponentDomainNotFoundError extends \Exception {
  public string $domainName;
}

class ServerComponentManager {
  public array $domains = [];

  public function __construct(
    private Accela $accela
  ){}

  public function loadServerComponent(string $domain, string $componentName): ServerComponent {
    if(!isset($this->domains[$domain])){
      $e = new ServerComponentDomainNotFoundError("server component domain '{$domain}' not founds.");
      $e->domainName = $domain;
      throw $e;
    }

    $path = rtrim($this->domains[$domain], "/") . "/{$componentName}.php";

    if(!is_file($path)){
      $e = new ServerComponentNotFoundError("'{$componentName}' server component not founds.");
      $e->componentName = $componentName;
      throw $e;
    }

    return new ServerComponent($path);
  }

  public function registerDomain(string $domain, string $path){
    $this->domains[$domain] = $path;
  }
}