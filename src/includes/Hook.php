<?php

namespace Accela;

class Hook {
  public static $hooks = [];

  public static function add(string $name, callable $callback): void {
    if(el(self::$hooks, $name)) $hooks[$name] = [];
    self::$hooks[$name][] = $callback;
  }

  public static function get($name): array {
    return el(self::$hooks, $name, []);
  }

  public static function exec($name, ...$args){
    foreach(self::get($name) as $hook){
      $hook(...$args);
    }
  }
}
