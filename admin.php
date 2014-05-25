<?php
/* teechan
 * https://github.com/tslocum/teechan
 * http://wakaba.c3.cx/shii/shiichan
 *
 * Admin panel
 */

require "include.php";

// Login screen
function login($why) {
    $mesg = "";
    if ($why != 0) {
        setcookie("teeaccname", "",
            time() - 3600);
        setcookie("teeacckey", "", time() - 3600);
    }
    switch ($why) {
        case 1:
            $mesg = "Bad password";
            continue;
        case 2:
            $mesg = "Bad username";
            continue;
        case 3:
            $mesg = "Expired session";
            continue;
        case 4:
            $mesg = "Success";
            continue;
    } ?>
    <style type="text/css">
        #logo {
            float: right;
            position: fixed;
            bottom: 0;
            right: 0;
            z-index: 999;
        }

        * {
            font-family: Tahoma, sans-serif
        }

        th {
            border: 2px solid #faf;
            background: #fdf
        }

        input {
            border: 2px solid #aaf;
            background: #ddf
        }

        td[colspan="2"] {
            text-align: right
        }</style>
    <?= $mesg ?>
    <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
    <form action="admin.php" method="POST">
        <table>
            <tr>
                <th colspan="2">Verify Administration Access</th>
            </tr>
            <tr>
                <td>Username</td>
                <td><input name="teeaccname"></td>
            </tr>
            <tr>
                <td>Password</td>
                <td><input type="password" name="teeaccpass"></td>
            </tr>
            <tr>
                <td colspan="2"><input type="submit" value="Login"></td>
            </tr>
        </table>
    </form>
    <?php exit;
}

// Check for login submission or stored session key
if ((!$_COOKIE['teeaccname'] || !$_COOKIE['teeacckey']) && (!isset($_POST['teeaccname']) && !isset($_POST['teeaccpass']))) {
    login(0);
}
$loggedin = false;
// Check passwor or stored session key
if (isset($_POST['teeaccpass'])) {
    $myaccount = checkCredentials($_POST['teeaccname'], $_POST['teeaccpass']);
} else {
    $myaccount = checkLoginKey($_COOKIE['teeaccname'], $_COOKIE['teeacckey']);
}
if (is_array($myaccount)) { // Credentials/key validated
    $loggedin = true;
    $mylevel = intval($myaccount['level']);

    $_COOKIE['teeaccname'] = $myaccount['username'];
    $_COOKIE['teeacckey'] = $myaccount['loginkey'];
} else { // Invalid credentials/key
    login($myaccount);
}
// Logged in
if (isset($_POST['teeaccname'])) {
    setcookie("teeaccname", $_COOKIE[teeaccname]);
    setcookie("teeacckey", $_COOKIE[teeacckey]);
}

################################################################################
// Okay, are we changing something?
if (get_magic_quotes_gpc()) $_POST = array_map("stripslashes", $_POST);
switch ($_POST[action]) {
    case "newadmin":
// making a new account
        if ($mylevel < 9000) fancyDie("You don't have permission for that.");
        if (!$_POST['password']) fancyDie("Blank password == a no-no.");
        if (!is_numeric($_POST['level'])) fancyDie("{$_POST['level']} isn't a number.");
        if ($_POST['level'] > 9999) fancyDie("9999 is the highest it goes. Dragonball Z power levels are not allowed.");
        if ($mylevel < 9999 && $_POST['level'] > 9000) fancyDie("No, your elite account-making abilities will not let you make yourself the sysop.");
        if ($_POST['password'] != $_POST['pass2']) fancyDie("Passwords didn't match");

        $existing_account = accountByUsername($_POST['addname']);
        if (is_array($existing_account)) fancyDie("There's already an admin by that name.");

        addAccount($_POST['addname'], $_POST['password'], $_POST['level']);

        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        The user <?= $_POST[addname] ?> was successfully added with the level <?= $_POST['level'] ?>.<p>
        <a
            href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "chgadmin":
// changing admin userlevel
        if ($mylevel < 9000) fancyDie("You don't have permission for that.");
        if (!is_numeric($_POST['level'])) fancyDie("{$_POST['level']} isn't a number.");
        if ($_POST['level'] > 9999) fancyDie("9999 is the highest it goes. Dragonball Z power levels are not allowed.");
        if ($mylevel < 9999 && $_POST['level'] > 9000) fancyDie("Nice try, Mr. Power Trip. (Can't empower a user to higher than level 9000.)");
// scan file

        $change_account = accountByUsername($_POST['addname']);
        if (!is_array($change_account)) fancyDie("Couldn't find an admin by that name.");

        if ($mylevel < 9999 && intval($change_account['level']) > 9000)
            fancyDie("You don't have permission for that.");

// rewrite file
        updateAccountLevel($change_account['username'], $_POST['level']);
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        The user <?= $_POST[addname] ?>'s level was successfully changed to <?= $_POST['level'] ?>.<p>
        <a
            href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "pleasechangemypasswordthankyou":
// changing your password
        if ($mylevel < 1) fancyDie("You don't have permission for that.");
        if (!$_POST['p1']) fancyDie("Blank password == a no-no.");
        if (isset($_POST['teeaccpass'])) fancyDie("You didn't enter your current password.");
        if (!password_verify($_POST['teeaccpass'], $myaccount['password'])) fancyDie("You didn't enter your current password correctly.");
        if ($_POST['p1'] != $_POST['p2']) fancyDie("Passwords didn't match");
        if (!is_array($myaccount)) fancyDie("You don't seem to exist.");

        updateAccountPassword($myaccount['username'], $_POST['p1']);
        login(4);
    case "deladmin":
// removing an admin entirely
        if ($mylevel < 9500) fancyDie("You don't have permission for that.");
        if (!$_POST[confirm]) fancyDie("You didn't confirm deletion. (Sorry, it's a safety catch.)");

        $delete_account = accountByUsername($_POST['addname']);
        if (!is_array($delete_account)) fancyDie("Couldn't find an admin by that name.");

        if ($mylevel < 9999 && intval($delete_account['level']) > 9000) fancyDie("You don't have permission for that.");

        deleteAccountByUsername($_POST['addname']);
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        >The user <?= $_POST[addname] ?>'s level was successfully deleted from the database.<p><a
        href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "modifymycapcodebecauseilikecapcodes":
// Modify capcode
        if ($mylevel < 10) fancyDie("You don't have permission for that.");

        updateAccountCapcode($myaccount['username'], $_POST['cap']);
        ?>
        <link rel="stylesheet"
              href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        Your capcode is now
        <b><?= $_POST[cap] ?></b>.<p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "modifysomeoneelsescapcode":
        if ($mylevel < 9000) fancyDie("Access denied");

        $capcode_account = accountByUsername($_POST['user']);
        if (!is_array($capcode_account)) fancyDie("no admin by that name");
        if (intval($capcode_account['level']) > 9000 && $mylevel < 9999) fancyDie("no thanks");

        updateAccountCapcode($_POST['user'], $_POST['cap']);
        ?><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <?= $_POST['user'] ?>'s capcode is now
        <b><?= $_POST['cap'] ?></b>.<p><a href="admin.php">Back to Admin Panel</a>
        <link rel="stylesheet" href="admin.css">
        <?php exit;
    case "savesettings":
        if ($mylevel < 7000) fancyDie("You don't have permission for that.");
        $filename = "globalsettings.txt";
        $write = <<<EOF
forumname=$_POST[forumname]
urltoforum=$_POST[urltoforum]
encoding=$_POST[encoding]
skin=$_POST[skin]
image=$_POST[image]
boardname=$_POST[boardname]
nameless=$_POST[nameless]
aborn=$_POST[aborn]
maxres=$_POST[maxres]
haship=$_POST[haship]
namefield=$_POST[namefield]
postsperpage=$_POST[postsperpage]
fpthreads=$_POST[fpthreads]
fpposts=$_POST[fpposts]
fplines=$_POST[fplines]
additionalthreads=$_POST[additionalthreads]
posticons=$_POST[posticons]
EOF;
        $fp = fopen($filename, "w") or fancyDie("Couldn't open the settings file");
        fputs($fp, $write);
        fclose($fp) ?>
        <link rel="stylesheet"
              href="admin.css"><h1>Success</h1><img src="png"><?= $filename ?> was written successfully.<p><a
        href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "writehead";
        if ($mylevel < 4900) fancyDie("You don't have permission for that.");
        if (!$_POST[bbs]) fancyDie("no bbs?!");
        $fp = fopen("$_POST[bbs]/head.txt", "w") or fancyDie("Couldn't open the file");
        fwrite($fp, $_POST[file]);
        fclose($fp);
        ?>
        <link rel="stylesheet"
              href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        Header file was written succesfully.<p><a
        href="admin.php">Back
        to Admin Panel</a>
        <?php exit;
    case "addmohel":
        if ($mylevel < 6000) fancyDie("You don't have permission for that.");

        ?>
        <link rel="stylesheet"
              href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        That tripcode circumcision was added successfully.<p><a
        href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "newb":
        if ($mylevel < 8000) fancyDie("You don't have clearance for that.");
        if (!$_POST[boardname]) fancyDie("Every board deserves a directory.");
        if (!$_POST[namename]) fancyDie("Every board deserves a name.");
        if (is_dir($_POST[boardname])) fancyDie("That name is already in use.");
        mkdir($_POST[boardname]);
        mkdir($_POST[boardname] . "/dat");
        touch($_POST[boardname] . "/subject.txt");
        touch($_POST[boardname] . "/head.txt");
        $q = fopen($_POST[boardname] . "/localsettings.txt", "w");
        fputs($q, "boardname=$_POST[namename]\n");
        fclose($q);
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $setting[boardname] = $_POST[namename];

        RebuildThreadList($_POST[boardname], 1, true, false);
        ?>
        <link rel="stylesheet" href="admin.css">
        <h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <?= $_POST[namename] ?> was created successfully.<p><a href="admin.php">Back
        to
        Admin Panel</a>
        <?php exit;
    case "saveboardsettings":
        if ($mylevel < 5000) fancyDie("Fnord! You don't have clearance for that.");
        if (!$_POST[bbs]) fancyDie("No BBS specified?");
        $fp = fopen("$_POST[bbs]/localsettings.txt", "w") or fancyDie("can't open localsettings.txt");
        if ($_POST[forumname]) fputs($fp, "forumname=$_POST[forumname]\n");
        if ($_POST[urltoforum]) fputs($fp, "urltoforum=$_POST[urltoforum]\n");
        if ($_POST[boardname]) fputs($fp, "boardname=$_POST[boardname]\n");
        if ($_POST[nameless]) fputs($fp, "nameless=$_POST[nameless]\n");
        if ($_POST[aborn]) fputs($fp, "aborn=$_POST[aborn]\n");
        if ($_POST[overridename]) fputs($fp, "overridename=on\nnamefield=$_POST[namefield]\n");
        if ($_POST[overrideip]) fputs($fp, "overrideip=on\nhaship=$_POST[haship]\n");
        if ($_POST[encoding]) fputs($fp, "encoding=$_POST[encoding]\n");
        if ($_POST[maxres]) fputs($fp, "maxres=$_POST[maxres]\n");
        if ($_POST[postsperpage]) fputs($fp, "postsperpage=$_POST[postsperpage]\n");
        if ($_POST[fpthreads]) fputs($fp, "fpthreads=$_POST[fpthreads]\n");
        if ($_POST[fplines]) fputs($fp, "fplines=$_POST[fplines]\n");
        if ($_POST[fpposts]) fputs($fp, "fpposts=$_POST[fpposts]\n");
        if ($_POST[posticons]) fputs($fp, "posticons=$_POST[posticons]\n");
        if ($_POST[additionalthreads]) fputs($fp, "additionalthreads=$_POST[additionalthreads]\n");
        if ($_POST[overrideskin]) fputs($fp, "overrideskin=on\nskin=$_POST[skin]\n");
        if ($_POST[adminsonly]) fputs($fp, "adminsonly=$_POST[adminsonly]");
        fclose($fp);
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        The settings for /<?= $_POST[bbs] ?>/ have been updated.<p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "aborn":
        if ($mylevel < 1000) fancyDie("Fnord! You don't have clearance for that.");
        if (!is_numeric($_POST[id])) fancyDie("no post?");
        $thread = file("$_POST[bbs]/dat/$_POST[dat].dat") or fancyDie("couldn't open");
        list($name, $trip, $date, $message, $id, $ip) = explode("<>", $thread[$_POST[id]]);
        $thread[$_POST[id]] = "Aborn!<><>$date<>$_POST[abornmesg]<>Aborn!<>$ip";
        $k = fopen("$_POST[bbs]/dat/$_POST[dat].dat", "w") or fancyDie("couldn't write");
        foreach ($thread as $line) {
            fputs($k, $line);
        }
        fclose($k);
        ?>
        <meta http-equiv="refresh" content="0;admin.php?task=rebuild&bbs=<?= $_POST[bbs] ?>">
        Post succesfully aborned.
        <?php exit;
    case "editsubj":
        if ($mylevel < 8000) fancyDie("Fnord! You don't have clearance for that.");
        if (!$_POST[subj]) fancyDie("no subject? not good");
        $thread = file("$_POST[bbs]/dat/$_POST[dat].dat") or fancyDie("couldn't open");
        $thread[0] = "$_POST[subj]<=>$_POST[name]<=>$_POST[icon]\n";
        $k = fopen("$_POST[bbs]/dat/$_POST[dat].dat", "w") or fancyDie("couldn't write");
        foreach ($thread as $line) {
            fputs($k, $line);
        }
        fclose($k);
        ?>
        <meta http-equiv="refresh" content="0;admin.php?task=rebuildsubj&bbs=<?= $_POST[bbs] ?>">
        Thread subject edited.
        <?php exit;
    case "silentaborn":
        if ($mylevel < 1500) fancyDie("Fnord! You don't have clearance for that.");
        if (!is_numeric($_POST[id])) fancyDie("no post?");
        $thread = file("$_POST[bbs]/dat/$_POST[dat].dat") or fancyDie("couldn't open");
        list($name, $trip, $date, $message, $id, $ip) = explode("<>", $thread[$_POST[id]]);
        $thread[$_POST[id]] = "SILENT<>ABORN<>1234<>SILENT<>ABORN<>$ip";
        $k = fopen("$_POST[bbs]/dat/$_POST[dat].dat", "w") or fancyDie("couldn't write");
        foreach ($thread as $line) {
            fputs($k, $line);
        }
        fclose($k);
        ?>
        <meta http-equiv="refresh" content="0;admin.php?task=rebuild&bbs=<?= $_POST[bbs] ?>">
        Post succesfully aborned.
        <?php exit;
    case "enactban":
        if ($mylevel < 3000) fancyDie("Fnord! You don't have clearance for that.");
        if (!$_POST[ip]) fancyDie("no ip to ban");
        $fp = fopen("bans.cgi", "a");
        fwrite($fp, "$_POST[ip]<>$_POST[pubres]<>$_POST[privres]<>$_COOKIE[teeaccname]\n");
        fclose($fp);
        chmod("bans.cgi", 0770);
        if ($_POST[message]) {
            if (!is_numeric($_POST[id])) fancyDie("no post?");
            $thread = file("$_POST[bbs]/dat/$_POST[dat].dat") or fancyDie("couldn't open");
            list($name, $trip, $date, $message, $id, $ip) = explode("<>", $thread[$_POST[id]]);
            $thread[$_POST[id]] = "$name<>$date<>$trip<>$message<br><br><b style='color:red'>(USER WAS BANNED FOR THIS POST)</b><>$id<>$ip";
            $k = fopen("$_POST[bbs]/dat/$_POST[dat].dat", "w") or fancyDie("couldn't write");
            foreach ($thread as $line) {
                fputs($k, $line);
            }
            fclose($k);
        }
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <?= $_POST[ip] ?> banned successfully.<p>
        <?php if ($_POST[message]) echo "Ban message placed on post.<p>" ?>
        <a href='admin.php?task=rebuild&bbs=<?= $_POST[bbs] ?>'>Rewrite index.html</a><br>
        <a href="admin.php">Back to admin panel</a>
        <?php exit;
    case "delthread":
        if ($mylevel < 2000) fancyDie("Fnord! You don't have clearance for that.");
        unlink("$_POST[bbs]/dat/$_POST[dat].dat") or fancyDie("couldn't delete");
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$_POST[bbs]/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        RebuildThreadList($_POST[bbs], $_POST[dat], true, true);
        ?>
        <meta http-equiv="refresh" content="0;admin.php?task=rebuild&bbs=<?= $_POST[bbs] ?>">
        Thread was deleted successfully.
        <?php exit;
    case "unban":
        if ($mylevel < 3000) fancyDie("Fnord! You don't have clearance for that.");
        if (!$_POST[ip]) fancyDie("no ip?");
        $file = file("bans.cgi");
        for ($i = 0; $i < count($file); $i++) {
            list ($ip, $unused, $unused) = explode("<>", $file[$i]);
            if (strstr($_SERVER[REMOTE_ADDR], $ip)) $file[$i] = "";
        }
        $k = fopen("bans.cgi", "w") or fancyDie("couldn't write");
        foreach ($file as $line) {
            fputs($k, $line);
        }
        fclose($k);
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Success</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <?= $_POST[ip] ?> unbanned successfully.
        <p>
        <a href="admin.php">Back to admin panel</a>
        <?php exit;
    default:
        break;
}

################################################################################
// Okay, we're printing out some stuff.
switch ($_GET[task]) {
    default: // Admin panel
        ?>
            <link rel="stylesheet" href="admin.css"><h1>Registered User Options Panel</h1>
            <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
            Welcome, <b><?= $_COOKIE[teeaccname] ?></b>.
            Your current e-penis size is <b><?= $mylevel ?>cm (<?php
            if ($mylevel == 9999) echo "<span style='color:red'>Webmaster</span>";
            else if ($mylevel >= 9000) echo "Operator";
            else if ($mylevel >= 7000) echo "Administrator";
            else if ($mylevel >= 6000) echo "Mohel";
            else if ($mylevel >= 2000) echo "Cleanup Crew";
            else if ($mylevel >= 1000) echo "Cleanup Assistant";
            else if ($mylevel >= 10) echo "Super Capcode Man";
            else if ($mylevel >= 1) echo "Capcode Man";
            else                       echo "Worm";
            // User options
            ?>)</b>.
            <h2>My Options</h2>
            <ul><?php
        if ($mylevel >= 1) {
            echo "<li><a href='admin.php?task=janitor'>How to post with a capcode and manage threads</a>";
            echo "<li><a href='admin.php?task=password'>Change My Password</a>";
        }
        if ($mylevel >= 10)
            echo "<li><a href='admin.php?task=capcode'>Change My Capcode</a>";
        echo "<li><a href='admin.php?task=logout'>Logout</a>";
// Forum-wide management
        ?></ul><?php
        if ($mylevel >= 6000) {
            ?><h2>Forum-wide Management</h2><ul> <?php
            if ($mylevel >= 7000) {
                echo "<li><a href='admin.php?task=global'>Change Global Settings</a>";
                if (!file_exists("globalsettings.txt")) {
                    echo " <b>(needs setup)</b>";
                }
            }
            if ($mylevel >= 8000) echo "<li><a href='admin.php?task=createboard'>Create New Board</a>";
            if ($mylevel >= 6000) echo "<li><a href='admin.php?task=mohel'>Tripcode Circumcision</a>";
            if ($mylevel >= 9000) echo "<li><a href='admin.php?task=rebuildtop'>Rebuild top directory</a> (overwrites index.html)";
            if ($mylevel >= 9000) echo "<li><a href='admin.php?task=alladmins'>Manage all registered accounts</a>";
            if ($mylevel >= 7000) echo "<li><a href='admin.php?task=managebans'>Manage Banned Users</a>";
        } // end 6000+ bracket
        ?></ul><?php
// Board management
        if ($mylevel >= 1000) {
            $board = array();
            $handle = opendir('.');
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir("$file") && is_file("$file/localsettings.txt")) array_push($board, $file);
                }
            }
            closedir($handle);
            if ($board == array()) echo "<h2>You haven't set up any boards :(</h2>";
            else foreach ($board as $tmp) {
                echo "<h2>/<a href='$tmp'>$tmp</a>/ Management</h2><ul>";
                if ($mylevel >= 1000) echo "<li><a href='admin.php?task=rebuild&bbs=$tmp'>Rewrite index.html</a>";
                if ($mylevel >= 5000) echo "<li><a href='admin.php?task=settings&bbs=$tmp'>Change Settings</a>";
                if ($mylevel >= 4900) echo "<li><a href='admin.php?task=edithead&bbs=$tmp'>Edit header file</a>";
                if ($mylevel >= 8000) echo "<li><a href='admin.php?task=cleanup&bbs=$tmp'>Cleanup</a> (delete/rebuild forum)";
                echo "</ul>";
            }
        } //end 1000+ bracket
        exit;
    case "janitor":
        if ($mylevel < 10)
            fancyDie("This information is not meant for you!");
        ?>
        <link rel="stylesheet" href="admin.css">
        <a href="admin.php">Back to Admin Panel</a>

        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <h1>How to post with a capcode</h1>
        <ol>
            <li>Click the "advanced reply" link replying to a thread.
            <li>Fill in your admin username and password in the "Name" and "Pass" fields.
            <li>If you didn't get the password right, the post won't be made; you can go back and correct it.
            <li>By default, your capcode is in lower case and red. To change your capcode click "<a
                    href='admin.php?task=capcode'>Change My Capcode</a>".
        </ol>
        <h1>How to manage threads</h1>
        <ol>
            <li>Navigate to read.php for the thread/post you wish to stop or manage.
            <li>At the very bottom of the page, there will be a "Manage" link you can click.

            <li>From the page that results, you can aborn any post in the thread, ban a user, or delete the thread
                itself.
        </ol>
        <?php exit;
    case "alladmins":
        if ($mylevel < 9000) fancyDie("You don't have permission for that!");?>
        <link rel="stylesheet" href="admin.css"><h1>Manage All Registered Accounts</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a>
        <table>
            <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Userlevel</th>
                <th>Change</th>
            </tr><?php
            $accounts = allAccounts();
            // Print table of all users
            foreach ($accounts as $account) {
                echo "\n<tr><td>{$account['username']}</td><form action='admin.php' method='POST'><input type='hidden' name='action' value='chgadmin'><input type='hidden' name='addname' value='{$account['username']}'><td>N/A</td>";
                if (intval($account['level']) > 9000 && intval($account['level']) < 9999) echo "<td></form>{$account['level']}</td><td>N/A</td></tr>";
                else echo "<td><input name='level' value='{$account['level']}' size='4' maxlength='4'></td><td><input type='submit' value='Change'> <a href='admin.php?task=fixcapcode&amp;tochange={$account['username']}'>Change Capcode</a></form></td></tr>";
            }
            ?>
            <tr>
                <td>
                    <form action='admin.php' method='POST'><input type='hidden' name='action' value='newadmin'><input
                            name='addname' value='Add New'>
                </td>
                <td><input type="password" name="password" size="7"><input type="password" name="pass2" size="7"></td>
                <td><input name='level' size='4' maxlength='4'></td>
                <td><input type='submit' value='Add'>
                    </form></td>
            </tr>
            <?php if ($mylevel > 9500) echo "<tr><th>Username</th><th colspan='2'>Confirmation</th></tr><tr><td><form action='admin.php' method='POST'><input type='hidden' name='action' value='deladmin'><input name='addname' value='Delete User'></td><td colspan='2'>Input username and tick box <input type='checkbox' name='confirm'> to confirm.</td><td><input type='submit' value='Delete!'></td></tr>"; ?>
        </table><h2>Quick Guide to Userlevels</h2>
        <b>The webmaster</b> should have userlevel 9999.<br>
        <b>Extremely important operators</b> should have userlevel 9000.<br>
        <b>Board maintainers</b> should have userlevel 8000-6000.<br>
        <b>Cleanup crew</b> should have userlevel 2000.<br>
        <b>Random people with capcodes</b> should have userlevel 1 or 10.
        <h2>Full Guide to Userlevels</h2>
        <dl>
            <dt>9999
            <dd><b>Webmaster</b>: can change and delete everyone.
            <dt>9500
            <dd>Can delete all non-operators.
            <dt>9000
            <dd><b>Operator</b>: can change permissions levels of all non-operators.
            <dt>8000
            <dd>Can create and delete boards.
            <dt>7500
            <dd>Can create threads in "admins-only" forums.
            <dt>7000
            <dd><b>Administrator</b>: can manage forum settings.
            <dt>6500
            <dd>Can reply to threadstopped threads.
            <dt>6000
            <dd><b>Mohel</b>: can circumcise tripcodes.
            <dt>5000
            <dd>Can change board settings.
            <dt>4900
            <dd>Can edit board header files. (i.e., board rules)
            <dt>3000
            <dd>Can ban users.
            <dt>2000
            <dd><b>Cleanup Crew</b>: can delete and archive.
            <dt>1500
            <dd>Can aborn silently (deleted post is hidden.)
            <dt>1000
            <dd><b>Cleanup Assistant</b>: can threadstop and aborn.
            <dt>10
            <dd>Only allowed to edit his capcode.
            <dt>1
            <dd>Only allowed to change his password. (For people with capcodes whom you don't trust.)
            <dt>0
            <dd>Worm: Only given the option to log out. You're fired!
        </dl>
        <?php    exit;
    case "capcode":
        if ($mylevel < 10) fancyDie("Access denied");
// Capcode
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Edit Capcode</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a>
        <p><h2>Current capcode</h2><?php
        $code = $myaccount['capcode'];
        if (trim($code) == '') $code = "<b style='color:#f00'>$_COOKIE[teeaccname]</b>";
        ?>
        <?= $code ?>
        <br><h2>Change capcode</h2>
        <form action='admin.php' method='POST'>
            <input type="hidden" name="action" value="modifymycapcodebecauseilikecapcodes">
            <input name="cap" value="<?= $code ?>"><input type="submit" value="Change"></form>
        <?php exit;
    case "password": //pass
        ?>
        <link rel="stylesheet" href="admin.css">
        <h1>Change Password</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a><p>
        <form action='admin.php' method='POST'><h2>Current Password</h2>
            For security: <input type="password" name="teeaccpass"><br>

            <h2>New Password</h2>
            <input type="hidden" name="action" value="pleasechangemypasswordthankyou">New:
            <input type="password" name="p1"><br>Verify: <input type="password" name="p2">
            <br><input type="submit" value="Change Password and Logout"></form>
        <?php exit;
    case "fixcapcode":
        if ($mylevel < 9000) fancyDie("Access denied");

        $existing_account = accountByUsername($_GET[tochange]);
        if (!is_array($existing_account)) fancyDie("Couldn't find an admin by that name.");
        if (intval($existing_account['level']) > 9000 && $mylevel < 9999) fancyDie("no thanks");

        $code = $existing_account['capcode'];
        if (trim($code) == '') $code = "<b style='color:#f00'>$_GET[tochange]</b>" ?>
        <link rel="stylesheet" href="admin.css"><h1>Edit User's Capcode</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a>
        <p><h2><?= $_GET[tochange] ?>'s current capcode</h2><?= $code ?>
        <br><h2>Change capcode</h2>
        <form action='admin.php' method='POST'>
            <input type="hidden" name="action" value="modifysomeoneelsescapcode"><input
                type="hidden" name="user" value="<?= $_GET[tochange] ?>">
            <input name="cap" value="<?= $code ?>"><input type="submit" value="Change"></form>
        <?php exit;
    case "mohel":
        if ($mylevel < 6000) fancyDie("Access denied"); ?>
        <link rel="stylesheet" href="admin.css"><h1>Tripcode Circumcision</h1>
        <p>Enter a name and/or tripcode you want circumcised. To only match a tripcode, prepend the hash: <b>#.CzKQna1OU</b></p>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <form action='admin.php' method='POST'>
            <a href="admin.php">Back to Admin Panel</a>
            <p><input type="hidden" name="action"
                      value="addmohel"><input name="mohel"> <input type="submit" value="Circumcise"></form>
        <?php exit;
    case "global":
        if ($mylevel < 7000) fancyDie("Access denied");
        $global = @file("globalsettings.txt");
        if (!$global) { // Settings file not found.
            $mesg = "Please create the initial global settings file for this forum.";
            $SETTING[forumname] = "My Personal Channel";
            $SETTING[urltoforum] = "http://localhost/shiichan/";
            $SETTING[skin] = "boston";
            $SETTING[image] = "http://localhost/shiichan/logo.png";
            $SETTING[boardname] = "(Unnamed Board)";
            $SETTING[nameless] = "Anonymous";
            $SETTING[maxres] = "1000";
            $SETTING[aborn] = "Aborn!";
            $SETTING[postsperpage] = "40";
            $SETTING[fpposts] = "5";
            $SETTING[fplines] = "10";
            $SETTING[fpthreads] = "10";
            $SETTING[additionalthreads] = "10";
            $SETTING[posticons] = "checked";
        } else { // Settings file feund.
            $mesg = "Edit your global settings file here.";
            $global = array_map("htmlspecialchars", $global);
            foreach ($global as $tmp) { /*tmlspecialquotes($tmp);*/
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                if ($value == "on") $value = "checked";
                $SETTING[$name] = $value;
            }
        } ?>
        <link rel="stylesheet" href="admin.css"><h1>Change Global Settings</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a><p><?= $mesg ?>
        <form action="admin.php" method="POST"><h2>Basic Settings</h2>
            Forum name: <input name="forumname" value="<?= $SETTING[forumname] ?>" size="50">
            <br>URL to forum: <input name="urltoforum" value="<?= $SETTING[urltoforum] ?>" size="50"> (include trailing
            /)
            <br> <?php if ($SETTING[encoding]) echo "Your default character encoding is $SETTING[encoding] and it is unwise to change that.<input type='hidden' name='encoding' value='$SETTING[encoding]'>"; else echo "Character encoding: <select name='encoding'><option value='utf8'>UTF-8 (recommended)<option value='sjis'>Shift-JIS</select> (Once you set this, you can't change it)"; ?>
            <h2>Styles</h2>
            Skin: <select name="skin"><?php
                $board = array();
                $dir = opendir('skin');
                while (false !== ($file = readdir($dir))) {
                    if ($file != '.' && $file != '..') {
                        if (@is_file("skin/$file/name.txt")) array_push($board, $file);
                    }
                }
                closedir($dir); // list all skins
                if ($board == array()) fancyDie("</select>No skins?!");
                foreach ($board as $tmp) {
                    $name = file_get_contents("skin/$tmp/name.txt");
                    if ($SETTING[skin] == $tmp) echo "<option value='$tmp' selected>$name</option>";
                    else echo "<option value='$tmp'>$name</option>";
                } ?></select>

            <h2>Default names</h2>
            Untitled board name: <input name="boardname" value="<?= $SETTING[boardname] ?>">
            <br>Default nickname: <input name="nameless" value="<?= $SETTING[nameless] ?>">
            <br>Default aborn: <input name="aborn" value="<?= $SETTING[aborn] ?>">

            <h2>Boring things</h2>
            Maximum number of replies: <input name="maxres" value="<?= $SETTING[maxres] ?>" size="5">
            <br>Hash IP and display it next to post: <input type="checkbox" name="haship" <?= $SETTING[haship] ?>
            <br>Enable post icons: <input type="checkbox" name="posticons" <?= $SETTING[posticons] ?>
            <br>Add a Name field to the reply box (for use on small forums): <input type="checkbox"
                                                                                    name="namefield" <?= $SETTING[namefield] ?>>
            <br>Posts per page: <input name="postsperpage" value="<?= $SETTING[postsperpage] ?>" size="5">
            <br>Threads displayed on front page: <input name="fpthreads" value="<?= $SETTING[fpthreads] ?>" size="5">
            <br>Posts displayed on front page threads (not including first post): <input name="fpposts"
                                                                                         value="<?= $SETTING[fpposts] ?>"
                                                                                         size="5">
            <br>Lines displayed on front page threads: <input name="fplines" value="<?= $SETTING[fplines] ?>" size="5">
            <br>Additional threads linked in front page table: <input name="additionalthreads"
                                                                      value="<?= $SETTING[additionalthreads] ?>"
                                                                      size="5">
            <input type="hidden" name="action" value="savesettings">

            <p><input type="submit" value="Save Settings"></form>
        <?php exit;
    case "rebuild":
        if ($mylevel < 1000) fancyDie("You don't have clearance for that.");
// settings file
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$_GET[bbs]/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        RebuildThreadList($_GET[bbs], 1, true, false);
        ?>
        <link rel="stylesheet" href="admin.css">
        <meta http-equiv='refresh' content='1;<?= $setting[urltoforum] ?><?= $_GET[bbs] ?>/'>
        In a few seconds, I'll take you over to the front page...
        <p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "createboard":
        if ($mylevel < 8000) fancyDie("You don't have clearance for that."); ?>
        <link rel="stylesheet" href="admin.css">
        <h1>Create New Board</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <form action="admin.php"
              method="POST"><input type="hidden" name="action" value="newb">
            Directory name: <input name="boardname"><br>
            Board name: <input name="namename"><br>
            <input type="submit" value="Create New Board">
        </form>
        <p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "rebuildtop":
        if ($mylevel < 9000) fancyDie("You don't have clearance for that.");
        $index = fopen("index.html", "w");
        // global settings
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }

        $board = array();
        $handle = opendir('.');
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                if (is_dir("$file") && is_file("$file/localsettings.txt")) array_push($board, $file);
            }
        }
        closedir($handle);
        $top = file_get_contents("skin/$setting[skin]/forumstop.txt");
        $top = str_replace("<%FORUMNAME%>", $setting[forumname], $top);
        $top = str_replace("<%FORUMURL%>", $setting[urltoforum], $top);
        fputs($index, $top);
        if (!$board) fputs($index, "<dt>No boards :(</dt>");
        else foreach ($board as $board_single) {
            $local = @file("$board_single/localsettings.txt");
            if ($local) {
                foreach ($local as $tmp) {
                    $tmp = trim($tmp);
                    list ($name, $value) = explode("=", $tmp);
                    $setting[$name] = $value;
                }
            }
            fputs($index, "<dt><a href='$board_single'>$setting[boardname]</a><dd>");
            fputs($index, file_get_contents("$board_single/head.txt"));
        }
        $bottom = file_get_contents("skin/$setting[skin]/forumsbottom.txt");
        $bottom = str_replace("<%TEEVERSION%>", $teeversion, $bottom);
        fputs($index, $bottom);
        fclose($index);

        ?>
        <link rel="stylesheet" href="admin.css">
        <h1>Success</h1>
        index.html was generated successfully.
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "edithead":
        if ($mylevel < 4900) fancyDie("no soup for you");
        if (!$_GET[bbs]) fancyDie("headache");
        ?>
        <link rel="stylesheet" href="admin.css">
        <h1>Editing head.txt for <?= $_GET[bbs] ?></h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <form action="admin.php" method="POST">
            <input type="hidden" name="action" value="writehead">
            <input type="hidden" name="bbs" value="<?= $_GET[bbs] ?>">
            <textarea rows="20" cols="80" name="file"><?php @readfile("$_GET[bbs]/head.txt"); ?></textarea><br>
            <input type="submit" value="Save settings">
        </form>
        <p><a href="admin.php">Back to Admin Panel</a>
        <?php exit;
    case "settings":
        if ($mylevel < 5000) fancyDie("You don't have clearance for that.");
        if (!$_GET[bbs]) fancyDie("No BBS selected?!");
        $local = file("$_GET[bbs]/localsettings.txt");
        if ($local) {
            $local = array_map("htmlspecialchars", $local);
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                if ($value == "on") $value = "checked";
                $SETTING[$name] = $value;
            }
        }
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Change Forum Settings for /<?= $_GET[bbs] ?>/</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        <a href="admin.php">Back to Admin Panel</a><p>
        <b>All settings filled in here will OVERRIDE global settings.</b>
        <form action="admin.php" method="POST">
            <h2>Basic Stuff</h2>
            <?php if ($SETTING[encoding]) echo "Your default character encoding is $SETTING[encoding] and it is unwise to change that.<input type='hidden' name='encoding' value='$SETTING[encoding]'>"; else echo "Character encoding: <select name='encoding'><option value=''>Don't override default<option value='utf8'>UTF-8 (recommended)<option value='sjis'>Shift-JIS</select> (Once you set this, you can't change it)"; ?>
            <br>
            Skin: <select name="skin"><?php
                $board = array();
                $dir = opendir('skin');
                while (false !== ($file = readdir($dir))) {
                    if ($file != '.' && $file != '..') {
                        if (@is_file("skin/$file/name.txt")) array_push($board, $file);
                    }
                }
                closedir($dir); // list all skins
                if ($board == array()) fancyDie("</select>No skins?!");
                foreach ($board as $tmp) {
                    $name = file_get_contents("skin/$tmp/name.txt");
                    if ($SETTING[skin] == $tmp) echo "<option value='$tmp' selected>$name</option>";
                    else echo "<option value='$tmp'>$name</option>";
                } ?></select> (Override?) <input name="overrideskin" <?= $SETTING[overrideskin] ?> type="checkbox">
            <br>Board name: <input name="boardname" value="<?= $SETTING[boardname] ?>">
            <br>Threads can only be started by admins? <input name="adminsonly" <?= $SETTING[adminsonly] ?>
                                                              type="checkbox">

            <h2>Default names</h2>
            <br>Default nickname: <input name="nameless" value="<?= $SETTING[nameless] ?>">
            <br>Default aborn: <input name="aborn" value="<?= $SETTING[aborn] ?>">

            <h2>Boring things</h2>
            Maximum number of replies: <input name="maxres" value="<?= $SETTING[maxres] ?>" size="5">
            <br>Hash IP and display it next to post: <input type="checkbox" name="haship" <?= $SETTING[haship] ?>>
            (Override?) <input name="overrideip" <?= $SETTING[overrideip] ?> type="checkbox">
            <br>Add a Name field to the reply box (for use on small forums): <input type="checkbox"
                                                                                    name="namefield" <?= $SETTING[namefield] ?>>
            (Override?) <input name="overridename" <?= $SETTING[overridename] ?> type="checkbox">
            <br>Posts per page: <input name="postsperpage" value="<?= $SETTING[postsperpage] ?>" size="5">
            <br>Threads displayed on front page: <input name="fpthreads" value="<?= $SETTING[fpthreads] ?>" size="5">
            <br>Posts displayed on front page threads (not including first post): <input name="fpposts"
                                                                                         value="<?= $SETTING[fpposts] ?>"
                                                                                         size="5">
            <br>Lines displayed on front page threads: <input name="fplines" value="<?= $SETTING[fplines] ?>" size="5">
            <br>Additional threads linked in front page table: <input name="additionalthreads"
                                                                      value="<?= $SETTING[additionalthreads] ?>"
                                                                      size="5">
            <input type="hidden" name="action" value="saveboardsettings">
            <input type="hidden" name="bbs" value="<?= $_GET[bbs] ?>">

            <p><input type="submit" value="Save Settings">

            <h2>Settings it would be unwise to override</h2>
            Global forum name: <input name="forumname" value="<?= $SETTING[forumname] ?>" size="50">
            <br>URL to forum: <input name="urltoforum" value="<?= $SETTING[urltoforum] ?>" size="50">
        </form>
        <?php exit;
    case "manage":
        if ($mylevel < 1000) fancyDie("You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[tid] or fancyDie("no thread?");
        $st = $_GET[st] or fancyDie("no start?");
        $to = $_GET[ed] or fancyDie("no end?");
// settings file
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$bbs/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
// some limits
        if (!is_numeric($st)) $st = 1;
        if ($to < $st) $to = $st;

// some errors
        if (!$bbs) fancyDie("You didn't specify a BBS.");
        if (!$key) fancyDie("You didn't specify a thread to read.");
        if (!file_exists("$bbs/dat/$key.dat")) fancyDie('That thread or board does not exist.');
        $thread = file("$bbs/dat/$key.dat");
        ?>
        <link rel="stylesheet" href="admin.css">
        <?php
        echo "<h1>Managing $bbs/$key</h1>";
        list ($threadname, $author, $threadicon) = explode("<=>", $thread[0]);

        $tmp = substr($threadname, 0, 40);
        echo "<h2>$tmp</h2>";
        if (is_writable("$bbs/dat/$key.dat")) echo "<a href='admin.php?bbs=$bbs&dat=$key&task=threadstop'>Threadstop</a>";
        else echo "<a href='admin.php?bbs=$bbs&dat=$key&task=unthreadstop'>Un-threadstop</a>";
        if ($mylevel > 1999) echo "<br><a href='admin.php?bbs=$bbs&dat=$key&task=delthread'>Delete Thread</a>";
        if ($mylevel > 7999) echo "<br><a href='admin.php?bbs=$bbs&dat=$key&task=editsubj'>Edit Subject</a>";
        echo "<table border='2'><tr><th>Name</th><th>Post</th><th>IP</th><th>Actions</th></tr>";

        for ($i = $st; $i <= $to; $i++) {
            list($name, $trip, $date, $message, $id, $ip) = explode("<>", $thread[$i]);
            $tmp = htmlspecialchars(substr($name, 0, 20));
            echo "<tr><td>$tmp";
            $tmp = htmlspecialchars(substr($message, 0, 40));
            echo "</td><td>$tmp</td><td>$ip</td><td><a href='admin.php?bbs=$bbs&dat=$key&id=$i&task=aborn'>Aborn</a> | <a href='admin.php?bbs=$bbs&dat=$key&id=$i&task=quietaborn'>Silent</a> | <a href='admin.php?bbs=$bbs&dat=$key&id=$i&task=ban'>Ban</a></td></tr>";
        }

        echo "</table><a href='" . linkToThread($bbs, $key, "$st-$to") . "'>Back to Thread</a>";
        exit;
    case "editsubj":
        if ($mylevel < 1000) fancyDie("Fnord! You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[dat] or fancyDie("no thread?");
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$bbs/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        $thread = file("$bbs/dat/$key.dat");
        list($subj, $name, $icon) = explode("<=>", $thread[0]);
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Edit Subject Confirmation</h1>
        <form action="admin.php" method="post"><p>
                <input type="hidden" name="bbs" value="<?= $bbs ?>">
                <input type="hidden" name="dat" value="<?= $key ?>">
                Subject: <input name="subj" value="<?= $subj ?>"><br>
                Name: <input name="name" value="<?= $name ?>"><br>
                Icon: <input name="icon" value="<?= $icon ?>"><br>
                <input type="hidden" name="action" value="editsubj">
                <input type="submit" value="Edit Subject">
        </form>
        <?php
        exit;
    case "aborn":
        if ($mylevel < 1000) fancyDie("Fnord! You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[dat] or fancyDie("no thread?");
        $id = $_GET[id] or fancyDie("no post?");
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$bbs/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Aborn Confirmation</h1>
        Replace this post:
        <blockquote>
            <?php
            $thread = file("$bbs/dat/$key.dat");
            list($name, $trip, $date, $message, $myid, $ip) = explode("<>", $thread[$id]);
            $tmp = htmlspecialchars(substr($name, 0, 20));
            echo "<b>$tmp</b><br>";
            $tmp = htmlspecialchars(substr($message, 0, 50));
            echo "$tmp";
            ?>...
        </blockquote>
        With this message:
        <form action="admin.php" method="post"><p>
                <input type="hidden" name="bbs" value="<?= $bbs ?>">
                <input type="hidden" name="dat" value="<?= $key ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="aborn">
                <input name="abornmesg" value="<?= $setting[aborn] ?>">
                <input type="submit" value="Confirm!">
        </form>
        <?php
        exit;
    case "quietaborn":
        if ($mylevel < 1500) fancyDie("Fnord! You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[dat] or fancyDie("no thread?");
        $id = $_GET[id] or fancyDie("no post?");
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$bbs/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Silent Aborn Confirmation</h1>
        Silently remove this post from the thread?
        <blockquote>
            <?php
            $thread = file("$bbs/dat/$key.dat");
            list($name, $trip, $date, $message, $myid, $ip) = explode("<>", $thread[$id]);
            $tmp = htmlspecialchars(substr($name, 0, 20));
            echo "<b>$tmp</b><br>";
            $tmp = htmlspecialchars(substr($message, 0, 50));
            echo "$tmp";
            ?>...
        </blockquote>
        <form action="admin.php" method="post"><p>
                <input type="hidden" name="bbs" value="<?= $bbs ?>">
                <input type="hidden" name="dat" value="<?= $key ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="silentaborn">
                <input type="submit" value="Confirm!">
        </form>

        <?php
        exit;
    case "ban":
        if ($mylevel < 3000) fancyDie("Fnord! You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[dat] or fancyDie("no thread?");
        $id = $_GET[id] or fancyDie("no post?");
        $glob = file("globalsettings.txt") or fancyDie("Eh? Couldn't fetch the global settings file?!");
        foreach ($glob as $tmp) {
            $tmp = trim($tmp);
            list ($name, $value) = explode("=", $tmp);
            $setting[$name] = $value;
        }
        $local = @file("$bbs/localsettings.txt");
        if ($local) {
            foreach ($local as $tmp) {
                $tmp = trim($tmp);
                list ($name, $value) = explode("=", $tmp);
                $setting[$name] = $value;
            }
        }
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Ban Confirmation</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        Ban the user who made this post?
        <blockquote>
            <?php
            $thread = file("$bbs/dat/$key.dat");
            list($name, $trip, $date, $message, $myid, $ip) = explode("<>", $thread[$id]);
            $tmp = htmlspecialchars(substr($name, 0, 20));
            echo "<b>$tmp</b><br>";
            $tmp = htmlspecialchars(substr($message, 0, 50));
            echo "$tmp";
            ?>...
        </blockquote>
        <form action="admin.php" method="post"><p>
                <input type="hidden" name="ip" value="<?= $ip ?>">
                IP: <?= $ip ?><br>
                Public reason: <input name="pubres" value=""> (will be displayed to banned user)<br>
                Private note: <input name="privres" value=""> (optional-- will only be visible to admins)<br>
                <input type="hidden" name="action" value="enactban">
                <input type="submit" value="Confirm!">
                <br><input type="checkbox" name="message"> Leave a <b style='color:red'>(USER WAS BANNED FOR THIS
                    POST)</b>
                message?
                <input type="hidden" name="bbs" value="<?= $bbs ?>">
                <input type="hidden" name="dat" value="<?= $key ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
        </form>


        <?php exit;
    case "delthread":
        if ($mylevel < 2000) fancyDie("Fnord! You don't have clearance for that.");
        $bbs = $_GET[bbs] or fancyDie("no board?");
        $key = $_GET[dat] or fancyDie("no thread?");
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Delete Confirmation</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        Really delete this thread?
        <form action="admin.php" method="post">
        <p>
        <input type="hidden" name="bbs" value="<?= $bbs ?>">
        <input type="hidden" name="dat" value="<?= $key ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="delthread">
        <input type="submit" value="Confirm!">
        <?php exit;
    case "threadstop";
        chmod("$_GET[bbs]/dat/$_GET[dat].dat", 0440) or fancyDie("couldn't chmod");
        ?>
        <meta http-equiv='refresh' content='0;admin.php?task=rebuild&bbs=<?= $_GET[bbs] ?>'>
        Thread was successfully stopped.
        <?php exit;
    case "unthreadstop";
        chmod("$_GET[bbs]/dat/$_GET[dat].dat", 0666) or fancyDie("couldn't chmod");
        ?>
        <meta http-equiv='refresh' content='0;admin.php?task=rebuild&bbs=<?= $_GET[bbs] ?>'>
        Thread was successfully unstopped.
        <?php exit; case "managebans":
    if ($mylevel < 3000) fancyDie("Fnord! You don't have clearance for that.");
    ?>
    <link rel="stylesheet" href="admin.css">
    <h1>Bans</h1>
    <a href="admin.php">Back to Admin Panel</a><br><br>
    <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
    <table border="2">
        <tr>
            <th>Banned User</th>
            <th>Public reason</th>
            <th>Private note</th>
            <th>Admin</th>
            <th>Unban?</th>
        </tr>
        <?php
        $bans = allBans();
        if (count($bans) > 0) {
            foreach ($bans as $ban) {
                echo "<tr><td>{$ban['ip']}</td><td>" . htmlentities($ban['pubreason']) . "</td><td>" . htmlentities($ban['privreason']) . "</td><td>{$ban['by']}</td><td><form action='admin.php' method='post'><input type='hidden' name='action' value='unban'><input type='hidden' name='ip' value='{$ban['ip']}><input type='submit' value='Unban'></form></td></tr>";
            }
        }
        else echo "<tr><td colspan='5'>NO BANS! HOORAY!</td></tr>";
        ?>
    </table>
    <?php exit;
    case "cleanup":
        if (!$_GET[bbs]) fancyDie("no bbs?");
        if ($mylevel < 8000) fancyDie("Fnord! You don't have clearance for that.");
        ?>
        <link rel="stylesheet" href="admin.css"><h1>Cleanup</h1>
        <div id="logo"><a href="https://github.com/tslocum/teechan"><img src="logo.png" id="logo" title="Powered by teechan"></a></div>
        Don't mess with these!
        <ul>
            <li><a href="admin.php?bbs=<?= $_GET[bbs] ?>&task=confirmdel">Delete entire forum</a> (XXX)
            <li><a href="admin.php?bbs=<?= $_GET[bbs] ?>&task=rebuildsubj">Rebuild subject.txt</a>
        </ul>
        <?php exit;
    case "rebuildsubj":
        if ($mylevel < 8000) fancyDie("Fnord! You don't have clearance for that.");
        $handle = opendir("$_GET[bbs]/dat/") or fancyDie("no board");
        $dats = array();
        while (false !== ($file = readdir($handle)))
            if (strstr($file, ".dat")) array_push($dats, $file);
        $finale = array();
        foreach ($dats as $dat) {
            $id = str_replace(".dat", "", $dat);
            $munge = file("$_GET[bbs]/dat/$dat");
            list($subj, $unused, $icon) = explode("<=>", $munge[0]);
            $icon = rtrim($icon);
            list($name, $trip, $unused, $unused, $unused, $unused) = explode("<>", $munge[1]);
            $name ? $namae = $name : $namae = '#' . $trip;
            $ll = count($munge) - 1;
            list($name, $trip, $lastid, $unused, $unused, $unused) = explode("<>", $munge[$ll]);
            $name ? $lastn = $name : $lastn = '#' . $trip;
            $finale[$lastid] = "$subj<>$namae<>$icon<>$id<>$ll<>$lastn<>$lastid\n";
        }
        krsort($finale);
        $fp = fopen("$_GET[bbs]/subject.txt", "w") or fancyDie("subject.txt writeerr!");
        foreach ($finale as $unused => $line) {
            echo "$unused<br>";
            fwrite($fp, $line);
        }
        fclose($fp);
        ?>
        <meta http-equiv="refresh" content="1;admin.php?task=rebuild&bbs=<?= $_GET[bbs] ?>">
        Subject.txt rewritten successfully.
        <?php
        exit;
    case "logout":
        login(0);
}
