<?php
    $server = "localhost";
    $user = "root";
    $pwd = "";
    $databasename = "thriftopia";

    $conn = new mysqli($server, $user, $pwd, $databasename);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

?>
