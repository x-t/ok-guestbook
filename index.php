<html>
<head>
<title>guestbook</title>
<style>
    #cdiv {
    	text-align: center;
    }
    #rdiv {
    	text-align: left;
    	display: inline-block;
    }
    body {
    	font-family: Arial;
    	color: black;
    	background-color: #EFB2D0;
    }
    a {
    	color: black;
    }
    #cmts {
        position: absolute;
        left: 10%;
        right: 10%;
        width: 80%;
        word-break: break-word;
    }
    pre {
        white-space: pre-wrap;
    }
</style>
</head>
<body>
<div id="cdiv">
    <h2>guestbook</h2>
    <p>leave a comment!</p>
    <div id="rdiv">
        <p>the (extremely) bodged together code is available on <a href="https://github.com/x-t/ok-guestbook">github</a>!</p>
        <form action="result.php" method="post">
            (optional) Username:<br>
            <input type="text" name="uname" autocomplete="off" maxlength="32">
            <br>Message:<br>
            <input type="text" name="message" autocomplete="off" maxlength="512">
            <br><br>
            <input type="submit">
        </form>

        <br><br>
<div id="cmts">
<?php
$s = file_get_contents("comments.json");
if ($s === FALSE) {
    echo "sysadmin error: error reading comment file";
}
else if ($s === NULL) {
    echo "sysadmin error: file_get_conents may be disabled";
}

$cmts = json_decode($s);
$arr_cmts = array_reverse($cmts->comments);
foreach ($arr_cmts as $comment) {
    echo "<pre>[" . $comment->time . "] <strong>" . htmlspecialchars($comment->name) . "</strong>: " . htmlspecialchars($comment->data) . "</pre>\n";
}
?>
</div>
    </div>
</div>
</body>
</html>
