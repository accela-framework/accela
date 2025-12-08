<?php

namespace Accela;

class PageNotFoundError extends \Exception {}

class Page {
  protected Accela $accela;
  public string $path, $head, $meta, $body;
  public \DOMDocument $metaDom;
  public array $props;
  public bool $isDynamic;
  public string | null $staticPath = null;
  private string $filePath;

  public function __construct(string $path, Accela $accela){
    $this->accela = $accela;

    if(preg_match("@\\[.+\\]@", $path)){
      $path = "/404";
    }

    $filePath = $this->filePath = $path . (substr($path, -1) === "/" ? "index.html" : ".html");
    $absFilePath = $accela->getFilePath("/pages{$filePath}");

    if(!is_file($absFilePath)){
      $absFilePath = __DIR__ . "/../pages{$filePath}";
    }

    if(!is_file($absFilePath)){
      $this->staticPath = $path;
      $dynamicPath = $accela->pageManager->getDynamicPath($this->staticPath);

      if($dynamicPath){
        $filePath = $dynamicPath . (substr($dynamicPath, -1) === "/" ? "index.html" : ".html");
        $absFilePath = $accela->getFilePath("/pages{$filePath}");
        $content = file_get_contents($absFilePath);
        $this->initialize($dynamicPath, $content, $this->staticPath);
        $this->isDynamic = true;
        return;

      }else{
        throw new PageNotFoundError("'{$path}' template file not founds.");
      }
    }

    $content = file_get_contents($absFilePath);
    $content = preg_replace("/^[\\s\\t]+/mu", "", $content);
    $content = preg_replace("/\\n+/mu", "\n", $content);
    $this->initialize($path, $content);
    $this->isDynamic = false;
  }

  public function initialize(string $path, string $content, string|null $staticPath=null): void {
    $this->path = $path;
    $this->head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $this->head = preg_replace("@[ \t]+<@", "<", $this->head);
    $this->meta = preg_replace("@^.*<accela-meta>[\s\t\n]*(.+?)[\s\t\n]*</accela-meta>.*$@s", '$1', $content);
    $this->body = preg_replace("@^.*<body>[\s\t\n]*(.+?)[\s\t\n]*</body>.*$@s", '$1', $content);

    // get PageProps
    if($staticPath){
      preg_match_all("@\\[(.+?)\\]@", $path, $m_keys);

      $path_re = preg_replace("@(\\[.+?\\])@", "([^/]+?)", $path);
      preg_match("@{$path_re}$@", $staticPath, $m_vals);

      $query = [];
      foreach($m_keys[1] as $i => $key){
        $query[$key] = $m_vals[$i+1];
      }
      $this->props = $this->accela->pageProps->get($path, $query);

    }else{
      $this->props = $this->accela->pageProps->get($path);
    }

    // eval ServerComponent
    $this->head = $this->evaluateServerComponent($this->head, $this->props);
    $this->meta = $this->evaluateServerComponent($this->meta, $this->props);
    $this->body = $this->evaluateServerComponent($this->body, $this->props);

    $this->metaDom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $this->metaDom->loadHTML("<html><body>{$this->meta}</body></html>");
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

      $domain = "app";
      $componentName = $props["use"];
      if(strpos($componentName, ":") !== FALSE){
        list($domain, $componentName) = explode(":", $componentName);
      }

      $component = $this->accela->serverComponentManager->loadServerComponent($domain, $componentName);
      $evaluated_component = $component ? $component->evaluate($props, $content, $this->accela) : "";
      $html = str_replace($tag, $evaluated_component, $html);
    }

    return $html;
  }
}
