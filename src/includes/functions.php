<?php

namespace Accela {
  function isDynamicPath(string $path): bool {
    return !!preg_match("@\\[.+?\\]@", $path);
  }

  function capture(callable $callback): string {
    ob_start();
    $callback();
    $output = ob_get_contents();
    ob_end_clean();
    return $output ?: "";
  }

  function env(string $key, mixed $default = null): mixed {
    return Env::get($key, $default);
  }

  /**
   * Create DOMDocument and convert :attr="value" syntax to data-bind format
   */
  function createDom(string $html): \DOMDocument {
    // @ 属性を一時的にエスケープ（DOMDocument が削除するのを防ぐ）
    $html = preg_replace('/ @([a-zA-Z])/', ' data-accela-dynamic-$1', $html);

    $doc = new \DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<meta charset="UTF-8"><div id="accela-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // : で始まる属性がなければ変換スキップ
    if (strpos($html, ' :') !== false) {
      $xpath = new \DOMXPath($doc);
      /** @var \DOMElement $el */
      foreach ($xpath->query('//*[@*]') as $el) {
        $binds = [];
        $toRemove = [];

        /** @var \DOMAttr $attr */
        foreach ($el->attributes as $attr) {
          if (str_starts_with($attr->name, ':')) {
            $name = substr($attr->name, 1);
            if ($name === 'text') {
              $el->setAttribute('data-bind-text', $attr->value);
            } else if ($name === 'html') {
              $el->setAttribute('data-bind-html', $attr->value);
            } else {
              $binds[] = $name . ':' . $attr->value;
            }
            $toRemove[] = $attr->name;
          }
        }

        foreach ($toRemove as $name) {
          $el->removeAttribute($name);
        }

        if (!empty($binds)) {
          $existing = $el->getAttribute('data-bind');
          $new = $existing ? $existing . ',' . implode(',', $binds) : implode(',', $binds);
          $el->setAttribute('data-bind', $new);
        }
      }
    }

    return $doc;
  }

  /**
   * Get innerHTML from DOMDocument created by createDom()
   */
  function getHtmlFromDom(\DOMDocument $doc): string {
    $root = $doc->getElementById('accela-root');
    if (!$root) {
      return '';
    }

    $html = '';
    foreach ($root->childNodes as $child) {
      $html .= $doc->saveHTML($child);
    }

    // エスケープした @ 属性を戻す
    $html = preg_replace('/ data-accela-dynamic-([a-zA-Z])/', ' @$1', $html);

    return $html;
  }

  /**
   * Convert :attr="value" syntax to data-bind format (returns string)
   */
  function convertBindSyntax(string $html): string {
    return getHtmlFromDom(createDom($html));
  }
}