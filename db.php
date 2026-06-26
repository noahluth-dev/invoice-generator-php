<?php 

$server_name = "localhost";
$user_name = "root";
$password = "";
$db_name = "invoice";
$conn = "";

try{
 $conn = mysqli_connect($server_name, $user_name, $password, $db_name);
}
catch(mysqli_sql_exception $e){
    echo "Could not connect to the Database";
}

?>