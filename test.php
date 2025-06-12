<?php

// use src\database\dialects\Mysql;
// use src\database\dialects\Sqlite;

// include 'vendor/autoload.php';

// $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sentience;user=root;password=");
// // $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=sentience;user=postgres;password=postgres");
// // $pdo = new PDO('sqlite:./sqlite/sentience.sqlite3');

// $dialect = new Mysql();

// $string1 = 'test\'\\';
// $string2 = "test\"\\";

// echo 'Preg quote: ' . $pdo->quote($string1);
// echo PHP_EOL . PHP_EOL;
// echo 'Dialect: ' . $dialect->escapeString($string2);
// echo PHP_EOL . PHP_EOL;
// echo escape_chars('<<Dit is een escaped \" \' karakter, \% dit is een unescaped karakter " >>', ["'", '"']);

echo json_encode((array) 'test');
