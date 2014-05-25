<?php

require "include.php";

if ($_SERVER[REQUEST_METHOD] != 'GET') fancyDie('I POSTed your mom in the ass last night.');

if (isset($_GET['b']) && isset($_GET['t'])) {
    $request = 'read.php/' . (isset($_GET['b']) ? $_GET['b'] : '') . '/' . (isset($_GET['t']) ? $_GET['t'] : '') . '/' . (isset($_GET['p']) ? $_GET['p'] : '');
} else {
    $file_pos = strrpos($_SERVER['REQUEST_URI'], 'read.php');
    if ($file_pos === false) fancyDie('Unable to read your request!');

    $request = substr($_SERVER['REQUEST_URI'], $file_pos);
}

// settings file
$glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
foreach ($glob as $tmp) {
    $tmp = trim($tmp);
    list ($name, $value) = explode("=", $tmp);
    $setting[$name] = $value;
}

if ($request != '') {
    $pairs = explode('/', $request);
    $bbs = $pairs[1];
    $local = @file("$bbs/localsettings.txt");
    if ($local) {
        foreach ($local as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
    }
    $key = $pairs[2];
    if (!$pairs[3]) {
        $posts = array("1-");
        $st = 1;
        $to = $setting[postsperpage];
    } else {
        $posts = explode(',', $pairs[3]);
    }
}


// some errors
if (!$bbs) fancyDie("You didn't specify a BBS.");
if (!$key) fancyDie("You didn't specify a thread to read.");
if (!file_exists("$bbs/dat/$key.dat")) fancyDie('That thread or board does not exist.');

// go for it!
echo PrintThread($bbs, $key, $posts, true);
