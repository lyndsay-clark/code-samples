<?php
    define("SRC_PATH", "../src/");
    define("TMP_PATH", SRC_PATH.".tmp/");
    include_once(SRC_PATH."html-headers.php");
    
    if(array_pop(explode("/", getcwd())) != "tools")
        die("ERROR: This script must be run from it's local directory. 'tools/'\n");
    
    $outDirs = [
        "../srv/public_html/web-test/",
        "../app/www/"
    ];
    $dirs = [
        "js",
        "css",
        "img",
        "plugins"
    ];

    mkdir(TMP_PATH);
    foreach($dirs as $dir) {
        recurse_copy(SRC_PATH.$dir, TMP_PATH.$dir);
    }
    include "generate-html.php";
    include "generate-css.php"; 
    mkdir(TMP_PATH . "css/");
    copy(SRC_PATH."css/style.css", TMP_PATH."css/style.css");
    foreach($outDirs as $dir) {
        recurse_delete($dir);
        recurse_copy(TMP_PATH, $dir);
    }
    
    recurse_delete(TMP_PATH);
    
    function recurse_copy($src, $dst) { 
        $dir = opendir($src); 
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    recurse_copy($src . '/' . $file,$dst . '/' . $file); 
                } 
                else { 
                    copy($src . '/' . $file,$dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir); 
    }
    
    function recurse_delete($str) {
        if (is_file($str)) {
            return @unlink($str);
        }
        elseif (is_dir($str)) {
            $scan = glob(rtrim($str,'/').'/*');
            foreach($scan as $index=>$path) {
                recurse_delete($path);
            }
            return @rmdir($str);
        }
    }

?>