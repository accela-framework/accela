<?php

namespace Accela;

class Hook {
  public $hooks = [];

  public function add(string $name, callable $callback): void {
    if(el($this->hooks, $name)) $hooks[$name] = [];
    $this->hooks[$name][] = $callback;
  }

  public function get($name): array {
    return el($this->hooks, $name, []);
  }

  public function exec($name, ...$args){
    foreach($this->get($name) as $hook){
      $hook(...$args);
    }
  }
}
