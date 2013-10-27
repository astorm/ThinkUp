#!/usr/bin/env php
<?php
function input($string)
{
    echo $string . "\n] ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);        
    fclose($handle);
    return trim($line);
}

function hashPassword($password,$salt='')
{
    return hash('sha256', $password.$salt);
}

function error($string)
{
    exit("ERROR: $string\n");
}

function main($argv)
{
    $password = input("Enter New Password to create a ThinkUp Beta 2 Compatible Hash");    
    $salt     = hashPassword(mt_rand());
    $hash     = hashPassword($password,$salt);
    echo "In the `tu_owners` table, set the following field/value pairs","\n\n",
    "pwd:          " . $hash,"\n",
    "pwd_salt      " . $salt,"\n",
    "is_activated  " . "1"  ,"\n",
    "failed_logins " . "0"  ,"\n\n";
}
main($argv);