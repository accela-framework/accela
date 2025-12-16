<?php

namespace Accela;

use Exception;

require_once __DIR__ . "/functions.php";

set_exception_handler(function ($e) {
  require __DIR__ . "/../views/error.php";
  exit(1);
});

class Accela {
  private string $appDir;
  public string $url;
  public string $lang;
  private ?int $serverLoadInterval = null;
  private array $plugins;
  public StaticSiteGenerator $staticSiteGenerator;
  public PageProps $pageProps;
  public PagePaths $pagePaths;
  public PageManager $pageManager;
  public ComponentManager $componentManager;
  public ServerComponentManager $serverComponentManager;
  public OutputGenerator $outputGenerator;
  public API $api;
  public Hook $hook;
  private array $routes = ["GET" => [], "POST" => []];
  public array $ssgRoutes = [];
  private array $globalData = [];
  private static array $pluginPaths = [];

  public function __construct(array $config){
    $this->appDir = rtrim($config["appDir"], "/");
    $this->url = $config["url"] ?? "";
    $this->lang = $config["lang"] ?? "";
    $this->serverLoadInterval = $config["serverLoadInterval"] ?? null;
    $this->plugins = $config["plugins"] ?? [];

    $this->staticSiteGenerator = new StaticSiteGenerator($this);
    $this->pageProps = new PageProps($this);
    $this->pagePaths = new PagePaths($this);
    $this->pageManager = new PageManager($this);
    $this->componentManager = new ComponentManager($this);
    $this->serverComponentManager = new ServerComponentManager($this);
    $this->outputGenerator = new OutputGenerator($this);
    $this->api = new API();
    $this->hook = new Hook();
  }

  public function route(string $path): void {
    $this->usePlugin("app", $this->appDir);
    $this->usePlugin("accela", dirname(__DIR__));

    foreach($this->plugins as $pluginName => $args){
      if(self::$pluginPaths[$pluginName]){
        $this->usePlugin($pluginName, self::$pluginPaths[$pluginName], $args);
      }
    }

    if($path === "/assets/site.json"){
      if(php_sapi_name() !== "cli"){
        if($this->serverLoadInterval){
          header("Cache-Control: max-age=" . $this->serverLoadInterval);
        }
        header("Content-Type: application/json");
      }

      $pages = array_map(function($page){
        return [
          "path" => $page->path,
          "head" => $page->head,
          "content" => $page->body,
          "props" => $page->props
        ];
      }, $this->pageManager->all());
      echo json_encode($pages);
      return;
    }

    if($path === "/assets/js/accela.js"){
      if(php_sapi_name() !== "cli"){
        if($this->serverLoadInterval){
          header("Cache-Control: max-age=" . $this->serverLoadInterval);
        }
        header("Content-Type: text/javascript");
      }

      echo file_get_contents(__DIR__ . "/../static/modules.js");
      echo file_get_contents($this->appDir . "/script.js");
      foreach($this->plugins as $pluginName => $args){
        if(isset(self::$pluginPaths[$pluginName])){
          $scriptPath = rtrim(self::$pluginPaths[$pluginName], "/") . "/script.js";
          if(file_exists($scriptPath)) echo file_get_contents($scriptPath);
        }
      }
      echo file_get_contents(__DIR__ . "/../static/accela.js");
      return;
    }

    $routes = el($this->routes, el($_SERVER, "REQUEST_METHOD", "GET"));
    foreach($routes as $_path => $callback){
      if($path === $_path){
        $callback();
        return;
      }
    }

    if(preg_match("@/api/(.+)$@", $path, $m)){
      if($this->api->route($m[1])) return;
    }

    $paths = explode("/", $path);
    $paths = array_map(function($path){
      return strtolower(urlencode($path));
    }, $paths);
    $pathInfo = implode("/", $paths);

    try{
      $page = $this->pageManager->getPage($pathInfo);
    }catch(PageNotFoundError $e){
      $page = $this->pageManager->getNotFoundPage();
      http_response_code(404);
    }

    $this->loadTemplate($page);
  }

  public function loadTemplate($page){
    $accela = $this;
    include __DIR__ . "/../views/template.php";
  }

  public function api(string $path, callable $callback): void {
    $this->api->register($path, $callback);
  }

  public function apiPaths(string $dynamic_path, callable $get_paths): void {
    $this->api->registerPaths($dynamic_path, $get_paths);
  }

  public function globalProps(callable $getter): void {
    $this->pageProps->registerGlobal($getter);
  }

  public function getGlobalProp(string $key): mixed {
    return $this->pageProps->globalProps[$key];
  }

  public function pageProps(string $path, callable $getter): void {
    $this->pageProps->register($path, $getter);
  }

  public function pagePaths(string $path, callable $getter): void {
    $this->pagePaths->register($path, $getter);
  }

  public function addRoute(string $method, string $path, callable $callback, $ssg_route=false){
    $method = strtoupper($method);
    $this->routes[$method][$path] = $callback;
    if($method === "GET" && $ssg_route) $this->ssgRoutes[] = $path;
  }

  public function usePlugin(string $name, string $path, mixed $args=null){
    $this->componentManager->registerDomain($name, rtrim($path, "/") . "/components");
    $this->serverComponentManager->registerDomain($name, rtrim($path, "/") . "/server-components");

    if(file_exists(rtrim($path, "/") . "/plugin.php")){
      $pluginCallback = include rtrim($path, "/") . "/plugin.php";
      $pluginCallback($this, $args);
    }
  }

  function addHook(string $name, callable $callback): void {
    $this->hook->add($name, $callback);
  }

  public function getFilePath(string $path){
    return $this->appDir . "/" . ltrim($path, "/");
  }

  public function getUtime(): string {
    $now = time();
    $interval = $this->serverLoadInterval;
    if($interval) $now = $now - ($now % $interval);

    return el($_GET, "__t", "{$now}");
  }

  public function setData(string $key, mixed $value): void {
    $this->globalData[$key] = $value;
  }

  public function getData(string $key): mixed {
    return $this->globalData[$key] ?? null;
  }

  public static function registerPlugin(string $name, string $path){
    self::$pluginPaths[$name] = $path;
  }
}
