<?php

function getDb(): PDO
{
    
    static $pdo = null;

    if ($pdo instanceof PDO) {                            // to a void reconnecting to the db every time!
        return $pdo;
    }

    $config = require dirname(__DIR__) . '/config/database.php';  

    $dsn = sprintf(                                         //sprintf used to format string and insert values into placeholders 
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',   
        $config['host'],                                    //and replace the %s with $config['host']
        $config['port'],                                   //dsn is data Sourse Name and it tells the PDO
        $config['dbname'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,       // if Error happens PDO throws an exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,             // to fetch result from db as associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                        // use prepared statements for security
    ]);

    return $pdo;
}
