<?php
/*
Plugin Name: LSD Simple Cache
Plugin URI: http://www.bas-matthee.nl
Description: Cache the webpages to reduce serverload and database-requests. Speeds up your Wordpress website.
Author: Bas Matthee
Version: 1.0
Author URI: https://www.twitter.com/BasMatthee
*/

// Cache lifetime in minutes
$ttl = 60*60;
    
function get_current_url() {
    
    $http = 'http';
    
    if (isset($_SERVER['HTTPS'])) {
        
        if ($_SERVER["HTTPS"] == "on") {
            
            $http = 'https';
            
        }
        
    }
    
    return $http.'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    
}

function clear_lsd_cache() {
    
    global $cache_dir;
    
    if (is_dir($cache_dir)) { 
        
        $objects = scandir($cache_dir); 
        
        foreach ($objects as $object) { 
            
            if ($object != "." && $object != "..") { 
                
                if (filetype($cache_dir."/".$object) == "dir")  {
                    
                    rrmdir($cache_dir."/".$object); 
                    
                } else {
                    
                    unlink($cache_dir."/".$object);
                    
                }
                 
            } 
            
        } 
        
        reset($objects); 
        rmdir($cache_dir);
        
    } 
    
}

// Define cache directory
$cache_dir = wp_upload_dir();
$cache_dir = $cache_dir['basedir'].'/simple-cache/';
$cache_file = md5(get_current_url()).'.htm';

// Clear cache after detected POST
if (count($_POST) > 0 || isset($_GET['clear_lsd_simple_cache'])) {
    
    clear_lsd_cache();
    
}

// Front-end only
if (!is_admin()) {
    
    global $ttl,$cache_dir,$cache_file;
    
    ob_start();
    
    if (file_exists($cache_dir.$cache_file) === true) {
        
        // Serve cache
        if (filemtime($cache_dir.$cache_file) > time()-($ttl)) {
            
            ob_start();
            
            include $cache_dir.$cache_file;
            
            $contents = ob_get_clean();
            
            echo $contents.'<!-- Cached by LSD Simple Cache -->';
            exit;
            
        }
        
    }
    
    add_action('shutdown', function() {
        
        $final = '';
        
        $levels = count(ob_get_level());
    
        for ($i = 0; $i < $levels; $i++) {
            
            $final .= ob_get_clean();
            
        }
        
        echo apply_filters('final_output', $final);
        
    }, 0);
    
    add_filter('final_output', function($output) {
        
        global $cache_dir,$cache_file;
        
        $owner = fileowner(__FILE__);
        $group = filegroup(__FILE__);
        
        if (!is_dir($cache_dir)) {
            
            mkdir($cache_dir,0777,true);
            @chmod($cache_dir,0755);
            @chown($cache_dir,$owner);
            @chgrp($cache_dir,$group);
            
        }
        
        $http = 'http';
        
        if (isset($_SERVER['HTTPS'])) {
            
            if ($_SERVER["HTTPS"] == "on") {
                
                $http = 'https';
                
            }
            
        }
        
        $handle = fopen($cache_dir.$cache_file,'w');
        fputs($handle,$output);
        fclose($handle);
        
        @chown($cache_dir.$cache_file,$owner);
        @chgrp($cache_dir.$cache_file,$group);
        
        echo $output;
        
    });
    
}