<?php

namespace Accela;

class NoPagePathsError extends \Exception {}

class PagePaths {
  public array $getters = [];
  public array $memo = [];

  public function __construct(
    private Accela $accela
  ){}

  public function get(string $path): mixed {
    if(!isset($memo[$path])){
      if(!el($this->getters, $path)) throw new NoPagePathsError($path);
      $memo[$path] = call_user_func($this->getters[$path]);
    }

    return $memo[$path];
  }

  public function register(string $path, callable $getter): void {
    $this->getters[$path] = $getter;
  }
}
