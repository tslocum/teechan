<?php
/* teechan
 * https://github.com/tslocum/teechan
 * http://wakaba.c3.cx/shii/shiichan
 *
 * Settings (copy to settings.php)
 */

define('TEE_PRETTYURLS', false); // Use /read.php/boardname/1400999437/ instead of /read.php?b=boardname&t=1400999437 (requires URL rewriting)
define('TEE_SALT', "changeme"); // Enter some random data, and don't change this in the future (used for secure tripcodes)

define('TEE_PDODSN', "mysql:host=localhost;dbname=changeme;charset=utf8");
define('TEE_PDOUSER', "changeme");
define('TEE_PDOPASS', "changeme");
