<?php

namespace Accela {
  /**
   * @param array | object $object
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  function el(mixed $object, string $key, mixed $default = null): mixed
  {
    if (is_array($object)) {
      return isset($object[$key]) ? $object[$key] : $default;
    } else {
      return isset($object->$key) ? $object->$key : $default;
    }
  }

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
