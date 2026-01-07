<?php

namespace Accela;

class API {
  public array $map = [];
  public array $pathsList = [];

  public function route(string $path): bool {
    foreach($this->map as $_path => $callback){
      if($path === $_path){
        $this->responseHeader($path);
        $callback();
        return true;
      }

      // Catch-all routes: [...slug] → (.+)
      // Regular dynamic segments: [name] → ([^/]+?)
      $pathRegexp = preg_replace('/\[\.\.\.(.+?)\]/', '(.+)', $_path);
      $pathRegexp = preg_replace('/(\[.+?\])/', '([^/]+?)', $pathRegexp);

      if(preg_match("@^{$pathRegexp}$@", $path, $matches)){
        $this->responseHeader($path);
        $callback(API::buildQuery($_path, $matches));
        return true;
      }
    }

    return false;
  }

  public function buildQuery(string $path, array $matches): array {
    $query = [];

    // Extract parameter names including catch-all detection
    preg_match_all('@\[(\.\.\.)?([a-z]+)\]@', $path, $m);
    foreach($m[2] as $i => $key){
      $isCatchAll = !empty($m[1][$i]); // Check if it's a catch-all route
      $value = $matches[$i+1] ?? null;

      if($isCatchAll && $value !== null){
        // Split catch-all parameter into array
        $query[$key] = array_values(array_filter(explode('/', $value)));
      }else{
        $query[$key] = $value;
      }
    }

    return $query;
  }

  public function getAllPaths(): array {
    $paths = [];

    foreach($this->map as $path => $_){
      if(strpos($path, "[") === FALSE) $paths[] = $path;
    }

    foreach($this->pathsList as $_ => $getPaths){
      $paths = array_merge($paths, $getPaths());
    }

    return $paths;
  }

  public function responseHeader(string $path): void {
    if(php_sapi_name() === "cli") return;

    preg_match('/\.(.*?)$/', $path, $m);
    if(!$m) return;

    $mimes = [
      "json" => "application/json",
      "csv" => "text/csv",
      "html" => "text/html"
    ];

    $mime = $mimes[$m[1]] ?? "text/plain";
    header("Content-Type: {$mime}");
  }

  public function register(string $path, callable $callback): void {
    if(!preg_match('@^[/.a-z0-9\-_\[\]\.]+$@', $path)) return;
    $this->map[$path] = $callback;
  }

  public function registerPaths(string $dynamic_path, callable $getPaths): void {
    $this->pathsList[$dynamic_path] = function()use($getPaths){
      static $memo;
      if(!$memo) $memo = $getPaths();
      return $memo;
    };
  }
}
