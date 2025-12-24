<?php

namespace Accela;

class Component {
  public string $content;
  private string $filePath;

  public function __construct(string $filePath){
    $this->filePath = $filePath;
    $this->load($filePath);
  }

  public function load(string $filePath){
    $content = file_get_contents($filePath);
    $content = preg_replace("/^[\\s\\t]+/mu", "", $content);
    $content = preg_replace("/\\n+/mu", "\n", $content);
    $this->content = convertBindSyntax($content);
  }
}