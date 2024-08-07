<?php
// server portalnet 103.80.49.238
// $servername = "localhost";
// $username = "admineleave";
// $password = "7~b0x74sB";
// $dbname = "eleave"; 
// $port = "3306";
// $prefix = "app";
// $dbdriver = "mysql";

// server swmaxnet 115.178.63.11
// $servername = "localhost";
// $username = "swmaxnet_admin";
// $password = "%2Y2il5c0";
// $dbname = "swmaxnet_eleave"; 
// $port = "3306";
// $prefix = "app";
// $dbdriver = "mysql";

// xampp
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eleaveup"; 
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
    'leave_quota' => 'leave_quota',
    'leave_items' => 'leave_items',
    'logs' => 'logs',
    'shift' => 'shift',
    'shift_holidays' => 'shift_holidays',
    'shift_workdays' => 'shift_workdays',
    'user' => 'user',
    'user_meta' => 'user_meta',
  ),
);