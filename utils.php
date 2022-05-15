<?php

function getDBConnection(){
    include_once "./DB.php";
    $db = new Db;
    $db->openConnection();
    return $db;
}

/** function help you to make concatenation to your query criteria
 * you can choose any separator default is " , "
 * example: createCriteria(array("creating","test")) return creating , test; 
 */
function createCriteria($criteria,$separator=" AND "){
    $criteria = gettype($criteria)=="array"?$criteria = join($separator,$criteria):$criteria;
    return $criteria;
}

?>