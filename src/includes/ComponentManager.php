<?php

namespace Accela;

class ComponentNotFoundError extends \Exception {}
class ComponentDomainNotFoundError extends \Exception {}

class ComponentManager {
  public array $domains = [];

  public function __construct(
    private Accela $accela
  ){}

  public function loadComponent(string $domain, string $componentName): Component {
    if(!isset($this->domains[$domain])){
      throw new ComponentDomainNotFoundError("component domain '{$domain}' not founds.");
    }

    $filePath = rtrim($this->domains[$domain], "/") . "/{$componentName}.html";

    if(!is_file($filePath)){
      throw new ComponentNotFoundError("'{$filePath}' component not founds.");
    }

    return new Component($filePath);
  }

  /**
   * @return Component[]
   */
  public function all(): array {
    $walk = function(string $domain, string $dir, array &$components=[])use(&$walk): array {
      if(is_dir($dir)){
        foreach(scandir($dir) as $file){
          if(in_array($file, [".", ".."])) continue;

          $file_path = rtrim($dir, "/") . "/{$file}";

          if(is_dir($file_path)){
            $walk($domain, "{$file_path}/", $components);

          }else if(is_file($file_path) && preg_match("@.*\\.html$@", $file)){
            $path = str_replace(".html", "", $file_path);
            $path = str_replace($this->domains[$domain], "", $path);
            $path = ltrim($path, "/");
            $components[$domain === "app" ? $path : "{$domain}:{$path}"] = $this->loadComponent($domain, $path)->content;
          }
        }
      }

      return $components;
    };

    $components = [];
    foreach($this->domains as $domain => $dir){
      $components = [...$components, ...$walk($domain, $dir)];
    }
    return $components;
  }

  public function registerDomain(string $domain, string $path){
    $this->domains[$domain] = $path;
  }
}
