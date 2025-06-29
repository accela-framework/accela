<?php

namespace Accela;
require_once __DIR__ . "/functions.php";

class StaticSiteGenerator {
  private static int $time;

  public static function output(): void {
    $dir = ROOT_DIR . "/out";

    if(is_file($dir)){
      throw new \Exception("ディレクトリが作成できません。");

    }else if(file_exists($dir)){
      self::clearDir($dir);

    }else{
      mkdir($dir);
    }

    $root = ROOT_DIR;
    if(file_exists("{$root}/assets")){
      shell_exec("cp -r {$root}/assets {$dir}/assets");
    }
    self::$time = time();

    foreach(Page::getAllTemplatePaths() as $path){
      if(isDynamicPath($path)){
        foreach(PagePaths::get($path) as $_path){
          $file_path = "{$dir}{$_path}" . (preg_match("@.*/$@", $_path) ? "index.html" : ".html");
          self::getPage($_path, $file_path);
        }
      }else{
        $file_path = "{$dir}{$path}" . (preg_match("@.*/$@", $path) ? "index.html" : ".html");
        self::getPage($path, $file_path);
      }
    }

    foreach(API::getAllPaths() as $path){
      $file_path = "{$dir}/api/{$path}";
      self::getPage("/api/{$path}", $file_path);
    }

    self::getPage("/sitemap.xml", "{$dir}/sitemap.xml");

    if(!file_exists("{$dir}/assets/js")) mkdir("{$dir}/assets/js", 0755, true);
    self::getPage("/assets/site.json", "{$dir}/assets/site.json");
    self::getPage("/assets/js/accela.js", "{$dir}/assets/js/accela.js");
    file_put_contents("{$dir}/.htaccess", self::htaccess());
  }

  private static function getPage(string $path, string $file_path): void {
    $dir_path = dirname($file_path);
    if(!is_dir($dir_path)) mkdir($dir_path, 0755, true);

    ob_start();
    Accela::route($path);
    file_put_contents($file_path, ob_get_contents());
    ob_end_clean();
  }

  private static function clearDir(string $dir): bool {
    $result = true;

    $iter = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach($iter as $file){
      if($file->isDir()) {
        $result &= rmdir($file->getPathname());
      }else{
        $result &= unlink($file->getPathname());
      }
    }

    return !!$result;
  }

  private static function htaccess(): string {
    return <<<S
RewriteEngine on
RewriteCond %{THE_REQUEST} ^.*/index.html
RewriteRule ^(.*)index.html$ $1 [R=301,L]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^(.*)$ $1.html
S;
  }
}
