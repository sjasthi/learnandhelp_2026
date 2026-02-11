<?php
   DEFINE('DATABASE_HOST', 'localhost');
   DEFINE('DATABASE_DATABASE', 'learnandhelp_db');
   DEFINE('DATABASE_USER', 'root');
   DEFINE('DATABASE_PASSWORD', '');

   $db = new mysqli(
    DATABASE_HOST,
    DATABASE_USER,
    DATABASE_PASSWORD,
    DATABASE_DATABASE
);

if ($db->connect_error) {
    die('Connect Error (' . $db->connect_errno . '): ' . $db->connect_error);
}
?>