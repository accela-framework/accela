<?php
$dir = rtrim(APP_DIR, "/") . "/" . rtrim($props["relpath"], "/");
$csss = array_filter(array_map("trim", explode("\n", $content)));
foreach($csss as $fname){
  $path = $dir . $fname;
  if(file_exists($path)){
    $v = filemtime($path);
    echo "<link href=\"{$fname}?v={$v}\" rel=\"stylesheet\">";
  }
}
