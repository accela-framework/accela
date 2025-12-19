<?php

namespace Accela;

class Hook {
  public $hooks = [];

  public function add(string $name, callable $callback): void {
    if($this->hooks[$name] ?? null) $hooks[$name] = [];
    $this->hooks[$name][] = $callback;
  }

  public function get($name): array {
    return $this->hooks[$name] ?? [];
  }

  public function exec($name, ...$args){
    foreach($this->get($name) as $hook){
      $hook(...$args);
    }
  }
}
