<?php

namespace Accela;

class OutputGenerator {
  public function __construct(
    private Accela $accela
  ){}

  public function htmlHeader(Page $page): string {
    $pageCommon = $this->accela->pageManager->getPageCommon();
    $style = '<style class="accela-css">' . $pageCommon->getCss() . '</style>';
    $separator = '<meta name="accela-separator">';

    $head = implode("\n", [$pageCommon->getHead(), $style, $separator, $page->getHead()]);
    return $this->fixHeadNode($head);
  }

  public function initialData(Page $page): array {
    return [
      "entrancePage" => [
        "path" => $page->path,
        "head" => $page->getHead(),
        "content" => $page->getBody(),
        "props" => $page->props
      ],
      "globalProps" => $this->accela->pageProps->globalProps,
      "components" => $this->accela->componentManager->all(),
      "utime" => $this->accela->getUtime()
    ];
  }

  private function fixHeadNode(string $str): string {
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