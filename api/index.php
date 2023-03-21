<?php

ini_set("session.use_cookies", 0);

header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// use Ds\Map;
// use Ds\Set;

// CORS stuff
// https://stackoverflow.com/questions/53298478/has-been-blocked-by-cors-policy-response-to-preflight-request-doesn-t-pass-acce
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    http_response_code(200);
    exit();
}

http_response_code(500);
date_default_timezone_set("Europe/Rome");

ob_start();

$script_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";

$request_body = file_get_contents('php://input');
if ($request_body) {
    // print_r(substr($request_body, 0, 1000));
    $payload = json_decode($request_body, true);
    if ($payload) {
        $_REQUEST = array_merge($payload, $_REQUEST);
    }
}

if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
    session_id($_REQUEST['session_id']);
}
session_start();

require_once("config.php");
require_once("include.php");

$Action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$ret = [];
$ret['result'] = "OK";

switch ($Action) {
    case "login":
        $username = @checkField($_REQUEST['username'], "Missing username parameter");
        $password = @checkField($_REQUEST['password'], "Missing password parameter");
        $username = addslashes($username);

        $query = "SELECT * FROM users u
            WHERE u.username = '{$username}' AND u.deleted = 0";
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("User " . $username . " does not exist", 401);
        }
        $RowUser = $result->fetch_array(MYSQLI_ASSOC);
        if ($RowUser['password'] != $password) {
            dieWithError("Invalid password", 401);
        }

        $_SESSION['Login'] = $RowUser['id'];

        $ret['session_id'] = session_id();
        break;

    case "unDoLast":
        checkLogin();
        $query = "SELECT *
            FROM `annotations`
            WHERE user = '{$_SESSION['Login']}' AND isSexist != '0'
            ORDER BY ts DESC
            LIMIT 1";
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("No annotations can be undone", 401);
        }
        $Row = $result->fetch_array(MYSQLI_ASSOC);
        $query = "DELETE FROM annotations WHERE id = '{$Row['id']}'";
        $result = $mysqli->query($query);
        break;

    case "getNextTweet":
        checkLogin();
        $query = "SELECT t.*
            FROM tweets t
            LEFT JOIN annotations a
                ON t.id = a.tweet_id AND a.user = '{$_SESSION['Login']}'
            WHERE a.id IS NULL
                AND t.deleted = '0'
                AND t.cluster = (SELECT cluster FROM users WHERE id = '{$_SESSION['Login']}')
            ORDER BY t.ord, t.id
            LIMIT 1";
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("No tweets left", 401);
        }
        $Row = $result->fetch_array(MYSQLI_ASSOC);
        $ret['data'] = $Row;
        break;

    case "saveAnswer":
        checkLogin();
        $TweetRow = find("tweets", $_REQUEST['tid'], "Unable to find tweet ID");
        // TODO: check whether annotation already exists!
        // TODO: check that the cluster is right
        if (!preg_match("/^[1234]$/", $_REQUEST['answer']['isSexist'])) {
            dieWithError("Field sexist is mandatory");
        }
        if (!preg_match("/^[1234]$/", $_REQUEST['answer']['isMasc'])) {
            dieWithError("Field masculinity is mandatory");
        }
        if (!preg_match("/^[123]$/", $_REQUEST['answer']['isInapp'])) {
            dieWithError("Field inappropriate is mandatory");
        }

        $data = [];
        $data['user'] = $_SESSION['Login'];
        $data['tweet_id'] = $TweetRow['id'];
        $data['isSexist'] = $_REQUEST['answer']['isSexist'];
        $data['isToxicMasculinity'] = $_REQUEST['answer']['isMasc'];
        $data['isInappropriate'] = $_REQUEST['answer']['isInapp'];
        $data['comment'] = $_REQUEST['answer']['notes'];
        $query = queryinsert("annotations", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $ret['debug'] = $data;
        break;

    case "skipTweet":
        checkLogin();
        $TweetRow = find("tweets", $_REQUEST['tid'], "Unable to find tweet ID");
        // TODO: check whether annotation already exists!
        // TODO: check that the cluster is right
        $data = [];
        $data['user'] = $_SESSION['Login'];
        $data['tweet_id'] = $TweetRow['id'];
        $data['isSexist'] = 0;
        $data['isToxicMasculinity'] = 0;
        $data['isInappropriate'] = 0;
        $data['comment'] = "";
        $query = queryinsert("annotations", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $ret['debug'] = $data;
        break;

    case "userinfo":
        checkLogin();
        $Row = find("users", $_SESSION['Login'], "Unable to find user");
        unset($Row['password']);
        $ret['data'] = $Row;
        break;

    case "logout":
        unset($_SESSION['Login']);
        break;
}

http_response_code(200);
echo json_encode($ret);
