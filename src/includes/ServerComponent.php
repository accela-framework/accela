<?php

namespace Accela;

class ServerComponent {
  public string $filePath;

  public function __construct(string $filePath) {
    $this->filePath = $filePath;
  }

  public function evaluate(array $props, string $content): string {
    $sc = $this;

    return capture(function()use($sc, $props, $content): void {
      include $sc->filePath;
    });
  }
}
