<?php

namespace Accela;

class PageManager {
  public function __construct(
    private Accela $accela,
    private array $pages = []
  ){}

  /**
   * @param string $pathInfo
   * @return Page
   */
  public function getPage(string $pathInfo){
    return new Page($pathInfo, $this->accela);
  }

  public function getNotFoundPage(): Page {
    return $this->getPage("/404");
  }

  public function getPageCommon(): PageCommon {
    return new PageCommon("", $this->accela);
  }

  /**
   * @return Page[]
   */
  public function all(): array {
    static $pages;

    if(!$pages){
      $pages = [];

      foreach(self::getAllTemplatePaths() as $path){
        if(preg_match("@\\[.+\\]@", $path)){
          foreach($this->accela->pagePaths->get($path) as $_path){
            $pages[$_path] = new Page($_path, $this->accela);
          }

        }else{
          $pages[$path] = new Page($path, $this->accela);
        }

      }
    }

    return $pages;
  }

  /**
   * @return string[]
   */
  public function getAllTemplatePaths(): array {
    static $paths;
    $accela = $this->accela;

    if(!$paths){
      $walk = function($dir, &$paths=[])use(&$walk, $accela){
        foreach(scandir($dir) as $file){
          if(in_array($file, [".", ".."])) continue;

          $filePath = $dir . $file;
          if(is_dir($filePath)){
            $walk("{$filePath}/", $paths);

          }else if(is_file($filePath) && preg_match("@.*\\.html$@", $file)){
            $path = ($file === "index.html") ? $dir : str_replace(".html", "", $filePath);
            $path = str_replace($this->accela->getFilePath("/pages"), "", $path);
            $paths[] = $path;
          }
        }

        return $paths;
      };

      $paths = $walk($this->accela->getFilePath("/pages/"));
    }

    return $paths;
  }

  public function getDynamicPath(string $staticPath): string | null {
    static $memo;
    if(!$memo) $memo = [];

    if(!isset($memo[$staticPath])){
      $memo[$staticPath] = null;

      foreach($this->getAllTemplatePaths() as $path){
        if(strpos($path, "[") !== false){
          $re = preg_replace("@(\\[.+?\\])@s", "[^/]+?", $path);
          if(preg_match("@{$re}@", $path)){
            $staticPaths = $this->accela->pagePaths->get($path);
            if(in_array($staticPath, $staticPaths)){
              $memo[$staticPath] = $path;
              return $path;
            }
          }
        }
      }
    }

    return $memo[$staticPath];
  }
}
