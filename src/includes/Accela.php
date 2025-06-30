<?php

namespace Accela;

use Exception;

require_once __DIR__ . "/functions.php";


set_exception_handler(function ($e) {
  require_once __DIR__ . "/../views/error.php";
  exit(1);
});

class Accela {
  static private array $pluginModulePaths = [];
  static private array $routes = ["GET" => [], "POST" => []];
  static public array $ssg_routes = [];

  public static function route(string $path): void {
    self::addPlugin("app", APP_DIR);
    self::addPlugin("accela", dirname(__DIR__));

    if($path === "/assets/site.json"){
      if(defined("SERVER_LOAD_INTERVAL")){
        header("Cache-Control: max-age=" . constant("SERVER_LOAD_INTERVAL"));
      }
      header("Content-Type: application/json");
      $pages = array_map(function($page){
        return [
          "path" => $page->path,
          "head" => $page->head,
          "content" => $page->body,
          "props" => $page->props
        ];
      }, Page::all());
      echo json_encode($pages);
      return;
    }

    if($path === "/assets/js/accela.js"){
      if(defined("SERVER_LOAD_INTERVAL")){
        header("Cache-Control: max-age=" . constant("SERVER_LOAD_INTERVAL"));
      }
      header("Content-Type: text/javascript");
      echo file_get_contents(__DIR__ . "/../static/modules.js");
      foreach(self::$pluginModulePaths as $path){
        $path = rtrim($path, "/") . "/script.js";
        if(file_exists($path)) echo file_get_contents($path);
      }
      echo file_get_contents(__DIR__ . "/../static/accela.js");
      return;
    }

    $routes = el(self::$routes, el($_SERVER, "REQUEST_METHOD", "GET"));
    foreach($routes as $_path => $callback){
      if($path === $_path){
        $callback();
        return;
      }
    }

    if(preg_match("@/api/(.+)$@", $path, $m)){
      if(API::route($m[1])) return;
    }

    // $path_info = el($_SERVER, "PATH_INFO", "/");
    $paths = explode("/", $path);
    $paths = array_map(function($path){
      return strtolower(urlencode($path));
    }, $paths);
    $path_info = implode("/", $paths);

    try{
      $page = new Page($path_info);
    }catch(PageNotFoundError $e){
      $page = new Page("/404");
      http_response_code(404);
    }

    require_once __DIR__ . "/../views/template.php";
  }

  public static function api(string $path, callable $callback): void {
    API::register($path, $callback);
  }

  public static function apiPaths(string $dynamic_path, callable $get_paths): void {
    API::registerPaths($dynamic_path, $get_paths);
  }

  public static function globalProps(callable $getter): void {
    PageProps::registerGlobal($getter);
  }

  public static function getGlobalProp(string $key): mixed {
    return PageProps::$global_props[$key];
  }

  public static function pageProps(string $path, callable $getter): void {
    PageProps::register($path, $getter);
  }

  public static function pagePaths(string $path, callable $getter): void {
    PagePaths::register($path, $getter);
  }

  public static function addRoute(string $method, string $path, callable $callback, $ssg_route=false){
    $method = strtoupper($method);
    self::$routes[$method][$path] = $callback;
    if($method === "GET" && $ssg_route) self::$ssg_routes[] = $path;
  }

  public static function addPlugin(string $name, string $path){
    Component::registerDomain($name, rtrim($path, "/") . "/components");
    ServerComponent::registerDomain($name, rtrim($path, "/") . "/server-components");
    self::$pluginModulePaths[] = $path;

    if(file_exists(rtrim($path, "/") . "/page-init.php")){
      require_once rtrim($path, "/") . "/page-init.php";
    }
  }
}
