<?php

require_once '/var/www/vendor/autoload.php';

$mloader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views');
$m = new Mustache_Engine(['loader' => $mloader]);

$data = [];
session_start();

// If we've been passed in a new set of connection credentials, overwrite the ones in the session
$headervars = ["server", "username", "password"];
foreach ($headervars as $thisone) {
    if (array_key_exists($thisone, $_REQUEST)) {
        $_SESSION[$thisone] = $_REQUEST[$thisone];
    }
}

$server = array_key_exists("server", $_SESSION) ? $_SESSION["server"] : "host.docker.internal";
$username = array_key_exists("username", $_SESSION) ? $_SESSION["username"] : "root";
$password = array_key_exists("password", $_SESSION) ? $_SESSION["password"] : "my_secret_pw_shh";
$dbname = "";

error_reporting(0);
$db = new mysqli($server, $username, $password, $dbname);
$db2 = new mysqli($server, $username, $password, $dbname);

$data["dbOK"] = true;
if ($db -> connect_errno) {
    $data["dberror"] = true;
    $data["dbOK"] = false;
    $data["server"] = $server;
    $data["username"] = $username;
    $data["password"] = $password;
    $data["dberrornumner"] = $db->connect_errno;
}

if ($data["dbOK"]) {
    $data["databases"] = [];
    $sql = "SHOW DATABASES";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data["databases"][] = $row["Database"];
    }
    if (array_key_exists("database", $_REQUEST)) {
        $dbname = $_REQUEST["database"];
        $data["dbname"] = $dbname;
        $sql = "SHOW TABLES from $dbname";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $thistable = [];
            $thistable["name"] = $row["Tables_in_$dbname"];
            $thistable["columns"] = [];
            $tablename = $thistable["name"];
            $sql2 = "DESC $dbname.$tablename";
            $thistable["sql2"] = $sql2;
            $stmt2 = $db2->prepare($sql2);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $columns = [];
            while ($innerrow = $result2->fetch_assoc()) {
                $columns[] = $innerrow;
            }
            $thistable["columns"] = $columns;
            $data["tables"][] = $thistable;
        }
    }
}

echo $m->render('index', $data);
