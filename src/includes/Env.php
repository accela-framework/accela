<?php

namespace Accela;

class Env {
  private static array $vars = [];

  public static function load(string $path): void {
    if (file_exists($path)) {
      self::$vars = require $path;
    }
  }

  public static function get(string $key, mixed $default = null): mixed {
    return self::$vars[$key] ?? $default;
  }

  public static function all(): array {
    return self::$vars;
  }
}