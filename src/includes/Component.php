<?php

namespace Accela;

class ComponentNotFoundError extends \Exception {}
class ComponentDomainNotFoundError extends \Exception {}

class Component {
  public static array $domains = [];
  public string $content, $domain;

  public function __construct(string $component_name){
    $this->domain = "app";
    if(strpos($component_name, ":") !== FALSE){
      list($this->domain, $component_name) = explode(":", $component_name);
    }

    if(!isset(self::$domains[$this->domain])){
      throw new ComponentDomainNotFoundError("component domain '{$this->domain}' not founds.");
    }

    $abs_file_path = rtrim(self::$domains[$this->domain], "/") . "/{$component_name}.html";

    if(!is_file($abs_file_path)){
      throw new ComponentNotFoundError("'{$abs_file_path}' component not founds.");
    }

    $content = file_get_contents($abs_file_path);
    $content = preg_replace("/^[\\s\\t]+/mu", "", $content);
    $this->content = preg_replace("/\\n+/mu", "\n", $content);
  }

  /**
   * @return Component[]
   */
  public static function all(): array {
    $walk = function(string $domain, string $dir, array &$components=[])use(&$walk): array {
      if(is_dir($dir)){
        foreach(scandir($dir) as $file){
          if(in_array($file, [".", ".."])) continue;

          $file_path = rtrim($dir, "/") . "/{$file}";

          if(is_dir($file_path)){
            $walk($domain, "{$file_path}/", $components);

          }else if(is_file($file_path) && preg_match("@.*\\.html$@", $file)){
            $path = str_replace(".html", "", $file_path);
            $path = str_replace(Component::$domains[$domain], "", $path);
            $path = ltrim($path, "/");
            $components[$domain === "app" ? $path : "{$domain}:{$path}"] = new Component("{$domain}:{$path}");
          }
        }
      }

      return $components;
    };

    $components = [];
    foreach(self::$domains as $domain => $dir){
      $components = [...$components, ...$walk($domain, $dir)];
    }
    return $components;
  }

  public static function registerDomain(string $domain, string $path){
    self::$domains[$domain] = $path;
  }
}
