<?php
    session_start();
    if (isset($_POST["uname"]) && isset($_POST["message"])) {
        $name = $_POST["uname"];
        $msg = $_POST["message"];
    } // Else it might be an admin page request

    $time = date('m-d') . " " . date('h:i a');
    $utime = strval(time());
    $client_ip = $_SERVER['REMOTE_ADDR'];

    // --- User set variables ---
    $MAX_MSG_LEN = 512;                         // Max message lenght
    $MAX_NAME_LEN = 32;                         // Max name lenght
    $ALLOW_ANONS = true;                        // Allow anonymous posters (IP still gets logged)
    $INDEX_PAGE = "index.php";                  // The index page of guestbook, the one you post from
    $BAN_LIST = "/home/www-data/bans.json";     // Banned IP/word list
    $COMMENT_FILE = "comments.json";            // Where the comment JSON will be stored
    $LOG_FILE = "/home/www-data/log.json";      // Log file
    $RATE_LIMIT = "120";                        // Rate limit (in seconds)
    $ADMIN_SECRET = "verysecretpassword";     // Admin password, plain text for extra security.
    // --------------------------
    
    function readjson($fname) {
        $file = file_get_contents($fname);
        $json = json_decode($file);
        return $json;
    }

    function checkwords($str, $words) {
        $split = preg_split('/\s+/', $str);
        $msg = "";
        global $client_ip;
        global $utime;
        foreach ($split as $word) {
            if (in_array($word, $words)) {
                echo '<p id="error">You used a banned word.</p>';
                wlog("bword", $client_ip, $utime, "{$str}");
                exit();
            } else {
                $msg = $msg . " {$word}";
            }
        }

        return substr($msg, 1);
    }

    function wlog($type, $ip, $utime, $additional) {
        global $LOG_FILE;
        $j = readjson($LOG_FILE);
        if ($type == "success") {
            $i = count($j->success);
            $j->success[$i]->type = $type;
            $j->success[$i]->utime = $utime;
            $j->success[$i]->ip = $ip;
            $j->success[$i]->add = $additional;
        } else {
            $i = count($j->err);
            $j->err[$i]->type = $type;
            $j->err[$i]->utime = $utime;
            $j->err[$i]->ip = $ip;
            $j->err[$i]->add = $additional;
        }

        $j = json_encode($j);
        file_put_contents($LOG_FILE, $j);

        return;
    }

    function findkey($key, $arr) {
        $k = NULL;
        $i = 0;
        foreach ($arr as $x) {
            foreach ($x as $y) {
                if ($y == $key) {
                    $k = $key;
                    goto l_end;
                }
            }
            $i++;
        }
        l_end:
        return $k == $key ? $i : NULL;
    }

    function findall($key, $arr) {
        $r = array();
        foreach ($arr as $a) {
            foreach ($a as $x) {
                if ($x == $key) {
                    array_push($r, $a);
                }
            }
        }

        return $r;
    }

    // ----------------------------------------
    //            admin page things
    // ----------------------------------------

    if (isset($_POST['req']) && $_POST['req'] == "adminp") {
        if (!isset($_POST['adminsecret']))
            exit();
        if ($_POST['adminsecret'] != $ADMIN_SECRET)
            exit();

        // Delete a comment
        if (isset($_POST['del_id'])) {
            $f = readjson($COMMENT_FILE);
            $id = $_POST['del_id'];
            unset($f->comments[$id]);
            $f->comments = array_values($f->comments);
            file_put_contents($COMMENT_FILE, json_encode($f));
            exit();
        }

        $filter = $_POST['filter'];
        $f = new StdClass();
        $log = readjson($LOG_FILE);
        if ($filter == "success") {
            $f->elements = $log->success;
        } else if ($filter == "ban") {
            $f->elements = findall("ban", $log->err);
        } else if ($filter == "anon") {
            $f->elements = findall("anon", $log->err);
        } else if ($filter == "rate_limit") {
            $f->elements = findall("rate_limit", $log->err);
        } else if ($filter == "namelen") {
            $f->elements = findall("namelen", $log->err);
        } else if ($filter == "msglen") {
            $f->elements = findall("msglen", $log->err);
        } else if ($filter == "admin") {
            $f->elements = findall("admin", $log->err);
        } else if ($filter == "bword") {
            $f->elements = findall("bword", $log->err);
        } else if ($filter == "errors") {
            $f->elements = $log->err;
        } else if ($filter == "posts") {
            $posts = readjson($COMMENT_FILE);
            $f->elements = $posts->comments;
        }

        echo json_encode($f);
        exit();
    } else {     // Apply the CSS
        echo '<link rel="stylesheet" type="text/css" href="style.css">';
    }

    // ----------------------------------------
    // ----------------------------------------


    if ($msg != "") {	
        $log = file_get_contents($LOG_FILE);
        $logs = json_decode($log, true);
        $bans = readjson($BAN_LIST);

        foreach ($bans->ip as $ip_ban) {
            if ($client_ip == $ip_ban) {
                wlog("ban", $client_ip, $utime, "${name}:${msg}");
                echo '<p id="error">You are banned from making posts.</p>';
                exit();
            }
        }

        if ($name == "") {
            if ($ALLOW_ANONS === true) {
                $name = "Anonymous";
            } else {
                wlog("anon", $client_ip, $utime, "{$msg}");
                echo '<p id="error">Anonymous posting is not allowed</p>';
                exit();
            }
        }
        $key = findkey($client_ip, array_reverse($logs['success']));
        if ($key >= 0) {
            $ar = array_reverse($logs['success']);
            $t = $utime - $ar[$key]['utime'];
            if ($t < $RATE_LIMIT) {
                wlog("rate_limit", $client_ip, $utime, "");
                echo '<p id="error">You\'re posting too fast.</p>';
                exit();
            }
        }

        $comjson = readjson($COMMENT_FILE);
       
        if (strlen($name) > $MAX_NAME_LEN) {
            wlog("namelen", $client_ip, $utime, "{$name}");
            echo '<p id ="error">Name was too large (>{$MAX_NAME_LEN} characters)';
            exit();
        }

        if (strlen($msg) > $MAX_MSG_LEN) {
            wlog("msglen", $client_ip, $utime, "{$name}:{$msg}");
            echo '<p id="error">Message was too large (>{$MAX_MSG_LEN} characters)';
            exit();
        }

        $msg = checkwords($msg, $bans->words);
        $name = checkwords($name, $bans->words);

        $i = count($comjson->comments);
        $comjson->comments[$i]->name = $name;
        $comjson->comments[$i]->data = $msg;
        $comjson->comments[$i]->time = $time;

        $jsonout = json_encode($comjson);
        file_put_contents($COMMENT_FILE, $jsonout);
        wlog("success", $client_ip, $utime, "");
    }
    
    header("Location: {$INDEX_PAGE}");
?>
