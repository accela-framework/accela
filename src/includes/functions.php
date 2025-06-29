<?php

namespace Accela {

  use function Accela\HtmlUtility\fixHeadNode;

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

  function getUtime(): string
  {
    $now = time();
    if (defined("SERVER_LOAD_INTERVAL")) $now = $now - ($now % constant("SERVER_LOAD_INTERVAL"));

    return el($_GET, "__t", "{$now}");
  }

  function getInitialData(Page $page): array
  {
    return [
      "entrancePage" => [
        "path" => $page->path,
        "head" => $page->head,
        "content" => $page->body,
        "props" => $page->props
      ],
      "globalProps" => PageProps::$global_props,
      "components" => getComponents(),
      "utime" => getUtime()
    ];
  }

  /**
   * @return Component[]
   */
  function getComponents(): array
  {
    $components = [];
    foreach (Component::all() as $name => $component) {
      $components[$name] = $component->content;
    }
    return $components;
  }

  function getHeaderHtml(Page $page): string
  {
    $common_page = PageCommon::instance();
    $style = '<style class="accela-css">' . $common_page->getCss() . '</style>';
    $separator = '<meta name="accela-separator">';

    $head = implode("\n", [$common_page->head, $style, $separator, $page->head]);
    return fixHeadNode($head);
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

  function addHook(string $name, callable $callback): void {
    Hook::add($name, $callback);
  }
}

namespace Accela\HtmlUtility {
  function fixHeadNode($str)
  {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML("<html><head>{$str}</head></html>");
    libxml_clear_errors();

    $head = $dom->getElementsByTagName('head')->item(0);

    $elements = $head->childNodes;
    $uniqueElements = [];

    foreach ($elements as $element) {

      if (!$element instanceof \DOMElement) continue;

      if ($element->nodeType == XML_ELEMENT_NODE) {
        $key = '';

        if ($element->tagName == 'title') {
          $uniqueElements['title'] = $element;
        } else if ($element->tagName == 'meta') {
          $name = $element->getAttribute('name');
          $property = $element->getAttribute('property');

          if ($name) {
            $key = 'meta_name_' . $name;
          } elseif ($property) {
            $key = 'meta_property_' . $property;
          } else {
            $key = $dom->saveHTML($element);
          }

          $uniqueElements[$key] = $element;

        } else {
          $uniqueElements[uniqid()] = $element;
        }
      }
    }

    return implode("", array_map(function($e)use($dom){return $dom->saveHTML($e);}, $uniqueElements));
  }
}
