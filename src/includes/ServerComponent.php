<?php

namespace Accela;

class ServerComponent {
  public string $filePath;

  public function __construct(string $filePath) {
    $this->filePath = $filePath;
  }

  public function evaluate(array $props, string $content, Accela $accela): string {
    $sc = $this;

    return capture(function()use($sc, $props, $content, $accela): void {
      include $sc->filePath;
    });
  }
}
