<?php

namespace Accela;

class PageNotFoundError extends \Exception {}

class Page {
  public string $path, $head, $meta, $body;
  public \DOMDocument $meta_dom;
  public array $props;
  public bool $is_dynamic;
  public string | null $static_path = null;
  private string $file_path;

  public function __construct(string $path){
    if(preg_match("@\\[.+\\]@", $path)){
      $path = "/404";
    }

    $file_path = $this->file_path = $path . (substr($path, -1) === "/" ? "index.html" : ".html");
    $abs_file_path = APP_DIR . "/pages{$file_path}";

    if(!is_file($abs_file_path)){
      $abs_file_path = __DIR__ . "/../pages{$file_path}";
    }

    if(!is_file($abs_file_path)){
      $this->static_path = $path;
      $dynamic_path = Page::getDynamicPath($this->static_path);

      if($dynamic_path){
        $file_path = $dynamic_path . (substr($dynamic_path, -1) === "/" ? "index.html" : ".html");
        $abs_file_path = APP_DIR . "/pages{$file_path}";
        $content = file_get_contents($abs_file_path);
        $this->initialize($dynamic_path, $content, $this->static_path);
        $this->is_dynamic = true;
        return;

      }else{
        throw new PageNotFoundError("'{$path}' template file not founds.");
      }
    }

    $content = file_get_contents($abs_file_path);
    $content = preg_replace("/^[\\s\\t]+/mu", "", $content);
    $content = preg_replace("/\\n+/mu", "\n", $content);
    $this->initialize($path, $content);
    $this->is_dynamic = false;
  }

  public function initialize(string $path, string $content, string|null $static_path=null): void {
    $this->path = $path;
    $this->head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $this->head = preg_replace("@[ \t]+<@", "<", $this->head);
    $this->meta = preg_replace("@^.*<accela-meta>[\s\t\n]*(.+?)[\s\t\n]*</accela-meta>.*$@s", '$1', $content);
    $this->body = preg_replace("@^.*<body>[\s\t\n]*(.+?)[\s\t\n]*</body>.*$@s", '$1', $content);

    // get PageProps
    if($static_path){
      preg_match_all("@\\[(.+?)\\]@", $path, $m_keys);

      $path_re = preg_replace("@(\\[.+?\\])@", "([^/]+?)", $path);
      preg_match("@{$path_re}$@", $static_path, $m_vals);

      $query = [];
      foreach($m_keys[1] as $i => $key){
        $query[$key] = $m_vals[$i+1];
      }
      $this->props = PageProps::get($path, $query);

    }else{
      $this->props = PageProps::get($path);
    }

    // eval ServerComponent
    $this->head = $this->evaluateServerComponent($this->head, $this->props);
    $this->meta = $this->evaluateServerComponent($this->meta, $this->props);
    $this->body = $this->evaluateServerComponent($this->body, $this->props);

    $this->meta_dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $this->meta_dom->loadHTML("<html><body>{$this->meta}</body></html>");
    libxml_clear_errors();
  }

  public function evaluateServerComponent(string $html, array $page_props): string {
    preg_match_all('@(<accela-server-component\s+(.+?)>(.*?)</accela-server-component>)@ms', $html, $m);
    foreach($m[1] as $i => $tag){
      $props_string = $m[2][$i];
      preg_match_all('/(@?[a-z0-9\-_]+)="(.+?)"/m', $props_string, $m2);
      $props = [];
      foreach($m2[1] as $j => $key){
        if(strpos($key, "@") === 0){
          $props[substr($key, 1)] = el($page_props, $m2[2][$j]);
        }else{
          $props[$key] = $m2[2][$j];
        }
      }

      $content = $m[3][$i];

      $component = ServerComponent::load($props["use"]);
      $evaluated_component = $component ? $component->evaluate($props, $content) : "";
      $html = str_replace($tag, $evaluated_component, $html);
    }

    return $html;
  }

  public static function getDynamicPath(string $static_path): string | null {
    static $memo;
    if(!$memo) $memo = [];

    if(!isset($memo[$static_path])){
      $memo[$static_path] = null;

      foreach(self::getAllTemplatePaths() as $path){
        if(strpos($path, "[") !== false){
          $re = preg_replace("@(\\[.+?\\])@s", "[^/]+?", $path);
          if(preg_match("@{$re}@", $path)){
            $static_paths = PagePaths::get($path);
            if(in_array($static_path, $static_paths)){
              $memo[$static_path] = $path;
              return $path;
            }
          }
        }
      }
    }

    return $memo[$static_path];
  }

  /**
   * @return Page[]
   */
  public static function all(): array {
    static $pages;
    if(!$pages){
      $pages = [];

      foreach(self::getAllTemplatePaths() as $path){
        if(preg_match("@\\[.+\\]@", $path)){
          foreach(PagePaths::get($path) as $_path){
            $pages[$_path] = new Page($_path);
          }

        }else{
          $pages[$path] = new Page($path);
        }

      }
    }

    return $pages;
  }

  /**
   * @return string[]
   */
  public static function getAllTemplatePaths(): array {
    static $paths;

    if(!$paths){
      $walk = function($dir, &$paths=[])use(&$walk){
        foreach(scandir($dir) as $file){
          if(in_array($file, [".", ".."])) continue;

          $file_path = $dir . $file;
          if(is_dir($file_path)){
            $walk("{$file_path}/", $paths);

          }else if(is_file($file_path) && preg_match("@.*\\.html$@", $file)){
            $path = ($file === "index.html") ? $dir : str_replace(".html", "", $file_path);
            $path = str_replace(APP_DIR . '/pages', "", $path);
            $paths[] = $path;
          }
        }

        return $paths;
      };

      $paths = $walk(APP_DIR . "/pages/");
    }

    return $paths;
  }
}
