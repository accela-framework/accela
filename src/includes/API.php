<?php

namespace Accela;

class API {
  public static array $map = [];
  public static array $paths_list = [];

  public static function route(string $path): bool {
    foreach(self::$map as $_path => $callback){
      if($path === $_path){
        self::responseHeader($path);
        $callback();
        return true;
      }

      $path_regexp = preg_replace('/(\[.+?\])/', '(.+?)', $_path);
      if(preg_match("@{$path_regexp}@", $path, $matches)){
        self::responseHeader($path);
        $callback(API::buildQuery($_path, $matches));
        return true;
      }
    }

    return false;
  }

  public static function buildQuery(string $path, array $matches): array {
    $query = [];

    preg_match_all('@\[([a-z]+)\]@', $path, $m);
    foreach($m[1] as $i => $key){
      $query[$key] = $matches[$i+1];
    }

    return $query;
  }

  public static function getAllPaths(): array {
    $paths = [];

    foreach(self::$map as $path => $_){
      if(strpos($path, "[") === FALSE) $paths[] = $path;
    }

    foreach(self::$paths_list as $_ => $get_paths){
      $paths = array_merge($paths, $get_paths());
    }

    return $paths;
  }

  public static function responseHeader(string $path): void {
    preg_match('/\.(.*?)$/', $path, $m);
    if(!$m) return;

    $mimes = [
      "json" => "application/json",
      "csv" => "text/csv",
      "html" => "text/html"
    ];

    $mime = el($mimes, $m[1], "text/plain");
    header("Content-Type: {$mime}");
  }

  public static function register(string $path, callable $callback): void {
    if(!preg_match('@^[/.a-z0-9\-_\[\]]+$@', $path)) return;
    self::$map[$path] = $callback;
  }

  public static function registerPaths(string $dynamic_path, callable $get_paths): void {
    self::$paths_list[$dynamic_path] = function()use($get_paths){
      static $memo;
      if(!$memo) $memo = $get_paths();
      return $memo;
    };
  }
}
