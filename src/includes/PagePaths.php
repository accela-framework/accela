<?php

namespace Accela;

class NoPagePathsError extends \Exception {}

class PagePaths {
  public static array $getters = [];
  public static array $memo = [];

  public static function get(string $path): mixed {
    if(!isset($memo[$path])){
      if(!el(self::$getters, $path)) throw new NoPagePathsError($path);
      $memo[$path] = call_user_func(self::$getters[$path]);
    }

    return $memo[$path];
  }

  public static function register(string $path, callable $getter): void {
    self::$getters[$path] = $getter;
  }
}
