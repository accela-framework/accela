<?php

namespace Accela;

class PageProps {
  public array $props = [];
  public array | null $globalProps = null;

  public function __construct(
    private Accela $accela
  ){}

  public function get(string $path, $query=null): mixed {
    if(!isset($this->props[$path])) return [];
    return $query ? call_user_func_array($this->props[$path], [$query]) : call_user_func($this->props[$path]);
  }

  public function register(string $path, callable $getter): void {
    $this->props[$path] = $getter;
  }

  public function registerGlobal(callable $getter): void {
    $this->globalProps = $getter();
  }
}
