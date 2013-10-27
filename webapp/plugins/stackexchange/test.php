<?php
function main($argv)
{
    $string = file_get_contents('etc/config.json');
    $object = json_decode($string);
    var_dump($object);
}
main($argv);