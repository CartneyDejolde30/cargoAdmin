<?php
$user = "root";
$host = "localhost";
$pass = "";
$db = "dbcargo";

$conn = New mysqli($host,$user,$pass,$db);

if($conn->connect_error){
    die("Error").$conn->connect_error;
}



?>