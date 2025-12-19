<?php

namespace Accela {
  function isDynamicPath(string $path): bool
  {
    return !!preg_match("@\\[.+?\\]@", $path);
  }

  function capture(callable $callback): string
  {
    ob_start();
    $callback();
    $output = ob_get_contents();
    ob_end_clean();
    return $output ?: "";
  }
}
