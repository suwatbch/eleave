<?php
// $servername = "localhost";
// $username = "id22283322_ptn_eleaveadmin"; //PTN_eleaveadmin / id22283322_ptn_eleaveadmin
// $password = "PTN_eleave1234"; //PTN_eleave1234
// $dbname = "id22283322_eleave"; //eleave / id22283322_eleave
// $port = "3306";
// $prefix = "app";
// $dbdriver = "mysql";

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "eleave"; 
$port = "3306";
$prefix = "app";
$dbdriver = "mysql";

return array (
  'mysql' => 
  array (
    'dbdriver' => $dbdriver,
    'username' => $username,
    'password' => $password,
    'dbname' => $dbname,
    'prefix' => $prefix,
    'hostname' => $servername,
    'port' => $port,
  ),
  'tables' => 
  array (
    'category' => 'category',
    'language' => 'language',
    'leave' => 'leave',
    'leave_cota' => 'leave_cota',
    'leave_items' => 'leave_items',
    'logs' => 'logs',
    'user' => 'user',
    'user_meta' => 'user_meta',
  ),
);