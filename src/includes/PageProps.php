<?php

namespace Accela;

class PageProps {
  public static array $props = [];
  public static array | null $global_props = null;

  public static function get(string $path, $query=null): mixed {
    if(!el(self::$props, $path)) return [];
    return $query ? call_user_func_array(self::$props[$path], [$query]) : call_user_func(self::$props[$path]);
  }

  public static function register(string $path, callable $getter): void {
    self::$props[$path] = $getter;
  }

  public static function registerGlobal(callable $getter): void {
    self::$global_props = $getter();
  }
}
