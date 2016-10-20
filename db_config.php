<?php
/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 12/10/15
 * Time: 5:14 PM
 */

function getDB() {
    // ucunion
    $dbhost="";
    $dbport="";
    $dbuser="";   
    $dbpass="";
    $dbname="";

    // test enviroment
    // $dbhost="";
    // $dbport="";
    // $dbuser="";
    // $dbpass="";
    // $dbname="";
    $dbConnection = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbConnection;
}
