<?php

namespace Accela;

class PageNotFoundError extends \Exception {}

class Page {
  protected Accela $accela;
  public string $path;
  public \DOMDocument $headDom, $metaDom, $bodyDom;
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

    // extract sections
    $head = preg_replace("@^.*<head>[\s\t\n]*(.+?)[\s\t\n]*</head>.*$@s", '$1', $content);
    $head = preg_replace("@[ \t]+<@", "<", $head);
    $meta = preg_replace("@^.*<(?:accela-meta|a-meta)>[\s\t\n]*(.+?)[\s\t\n]*</(?:accela-meta|a-meta)>.*$@s", '$1', $content);
    $body = preg_replace("@^.*<body>[\s\t\n]*(.+?)[\s\t\n]*</body>.*$@s", '$1', $content);

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
    $head = $this->evaluateServerComponent($head, $this->props);
    $meta = $this->evaluateServerComponent($meta, $this->props);
    $body = $this->evaluateServerComponent($body, $this->props);

    // create DOM and convert :attr syntax
    $this->headDom = createDom($head);
    $this->metaDom = createDom($meta);
    $this->bodyDom = createDom($body);
  }

  /**
   * Get innerHTML of DOM
   */
  public function getHtml(\DOMDocument $dom): string {
    return getHtmlFromDom($dom);
  }

  /**
   * Get head HTML
   */
  public function getHead(): string {
    return $this->getHtml($this->headDom);
  }

  /**
   * Get meta HTML
   */
  public function getMeta(): string {
    return $this->getHtml($this->metaDom);
  }

  /**
   * Get body HTML
   */
  public function getBody(): string {
    return $this->getHtml($this->bodyDom);
  }

  public function evaluateServerComponent(string $html, array $page_props): string {
    preg_match_all('@(<(?:accela-server-component|a-sc)\s+(.+?)>(.*?)</(?:accela-server-component|a-sc)>)@ms', $html, $m);
    foreach($m[1] as $i => $tag){
      $props_string = $m[2][$i];
      preg_match_all('/(@?[a-z0-9\-_]+)="(.+?)"/m', $props_string, $m2);
      $props = [];
      foreach($m2[1] as $j => $key){
        if(strpos($key, "@") === 0){
          $props[substr($key, 1)] = $page_props[$m2[2][$j]] ?? null;
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