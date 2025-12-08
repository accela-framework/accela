<?php

namespace Accela;
require_once __DIR__ . "/functions.php";

class StaticSiteGenerator {
  private int $time;

  public function __construct(
    private Accela $accela
  ){}

  public function output(array $config): void {
    $indexDir = rtrim($config["indexDir"] ?? $this->accela->getFilePath("/../.."), "/");
    $outputDir = rtrim($config["outputDir"] ?? "out", "/");
    $outputDir = $indexDir . "/" . $outputDir;
    $includeFiles = array_map(function($f){return rtrim($f, "/");}, $config["includeFiles"] ?? []);

    if(is_file($outputDir)){
      throw new \Exception("ディレクトリが作成できません。");

    }else if(file_exists($outputDir)){
      self::clearDir($outputDir);

    }else{
      mkdir($outputDir);
    }

    foreach($includeFiles as $includeFile){
      if(file_exists($includeFile)){
        shell_exec("cp -r \"{$indexDir}/{$includeFile}\" \"{$outputDir}/{$includeFile}\"");
      }
    }
    $this->time = time();

    foreach($this->accela->pageManager->getAllTemplatePaths() as $path){
      if(isDynamicPath($path)){
        foreach($this->accela->pagePaths->get($path) as $_path){
          $filePath = "{$outputDir}{$_path}" . (preg_match("@.*/$@", $_path) ? "index.html" : ".html");
          $this->getPage($_path, $filePath);
        }
      }else{
        $filePath = "{$outputDir}{$path}" . (preg_match("@.*/$@", $path) ? "index.html" : ".html");
        $this->getPage($path, $filePath);
      }
    }

    foreach($this->accela->api->getAllPaths() as $path){
      $filePath = "{$outputDir}/api/{$path}";
      $this->getPage("/api/{$path}", $filePath);
    }

    foreach($this->accela->ssgRoutes as $path){
      $this->getPage($path, "{$outputDir}{$path}");
    }

    if(!file_exists("{$outputDir}/assets/js")) mkdir("{$outputDir}/assets/js", 0755, true);
    $this->getPage("/assets/site.json", "{$outputDir}/assets/site.json");
    $this->getPage("/assets/js/accela.js", "{$outputDir}/assets/js/accela.js");
    file_put_contents("{$outputDir}/.htaccess", self::htaccess());
  }

  private function getPage(string $path, string $filePath): void {
    $dir_path = dirname($filePath);
    if(!is_dir($dir_path)) mkdir($dir_path, 0755, true);

    ob_start();
    $this->accela->route($path);
    file_put_contents($filePath, ob_get_contents());
    ob_end_clean();
  }

  private function clearDir(string $dir): bool {
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

  private function htaccess(): string {
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
