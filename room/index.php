<?php

require "../user/header.php";
require "../user/utility.php";

//QUERY KEY : create room
$KEY_REQUEST = "request";
$KEY_NAME = "name";
$KEY_PURPOSE = "purpose";
$KEY_IS_PUBLIC = "isPublic";
$KEY_PASSWORD = "password";
$KEY_HOST = "host";

//JSON KEY : search room
$KEY_TYPE = "type";
$KEY_WORD = "word";
#request

//JSON KEY : update room
$KEY_ROOM_ID = "roomId";
$KEY_PRIVATE_ID = "privateId";
#request

//QUERY KEY : delete room
#roomId
#privateId
#request

//QUERY KEY : change host
$KEY_USER_ID = "userId";
#roomId
#privateId
#request

//QUERY KEY : grant Parmission
#roomId
#privateId
#userId
#request

//QUERY KEY : delete member
#roomId
#privateId
#userId

//QUERY KEY : change room
#privateId
#roomId
#name
#purpose
#request

//QUERY KEY : affiliation room
#roomId
#privateId
#request

$req = $_SERVER["REQUEST_METHOD"];
if($req == "POST") {
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    if($request == "CREATE") {
        //create room
        $name = $json[$KEY_NAME];
        $purpose = $json[$KEY_PURPOSE];
        $isPublic = $json[$KEY_IS_PUBLIC];
        $password = $json[$KEY_PASSWORD];
        $host = $json[$KEY_HOST];
        echo create_room($name, $purpose, $isPublic, $password, $host);
        return;

    } else if($request == "AFFILIATE") {
        //affiliation room
        $roomId = $json[$KEY_ROOM_ID];
        $privateId = $json[$KEY_PRIVATE_ID];
        echo affiliation_room($roomId, $privateId);
        return;
    } 
} else if($req == "PUT") {
    $params = array ();
    parse_str(file_get_contents('php://input'), $params);
    if($request == "DELETE"){
        //delete room
        $roomId = $params[$KEY_ROOM_ID];
        $privateId = $params[$KEY_PRIVATE_ID];
        echo delete_room($roomId, $privateId);
        return;

    } else if($request == "CHANGE_HOST") {
        //change host
        $roomId = $params[$KEY_ROOM_ID];
        $privateId = $params[$KEY_PRIVATE_ID];
        $userId = $params[$KEY_USER_ID];
        echo change_host($roomId, $privateId);
        return;

    } else if($request == "GRANT") {
        //grant permission
        $roomId = $params[$KEY_ROOM_ID];
        $privateId = $params[$KEY_PRIVATE_ID];
        $userId = $params[$KEY_USER_ID];
        echo grant_permission($roomId, $privateId, $userId);
        return;

    } else if($request == "CHANGE") {
        //change room
        $privateId = $params[$KEY_PRIVATE_ID];
        $roomId = $params[$KEY_ROOM_ID];
        $name = $params[$KEY_NAME];
        $purpose = $params[$KEY_PURPOSE];
        echo change_room($privateId, $roomId, $name, $purpose);
        return;
    }
} else if($req == "DELETE") {
    //delete member
    $roomId = $params[$KEY_ROOM_ID];
    $privateId = $params[$KEY_PRIVATE_ID];
    $userId = $params[$KEY_USER_ID];
    echo delete_member($roomId, $privateId, $userId);
    return;

} else {
    //Error
    echo badreq();      
}

//functions
//create room
function create_room($name, $purpose, $isPublic, $password, $host) {
    //need host
    if(empty($host)){ 
        return badreq();
    }

global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        //new room
        $pdo->beginTransaction();
        try {
            if(empty($name)) $name = 'ROOM'; //$nameが空なら'ROOM'をセット
            //SQL
            $sql = "INSERT INTO pacco.room
            (name, purpose, isPublic, password, host)
            VALUES 
            (:name, :purpose, :isPublic, :password, :host)";
            //INSERT
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':name' => $name,
                ':purpose' => $purpose,
                ':isPublic' => $isPublic,
                ':password' => $password,
                ':host' => $host
            );
            $stmt -> execute($params);
            $pdo -> commit();
            return ok();

        } catch (Exception $e) {
            $pdo -> rollBack();
            echo servererr();
            die();
        }
     } catch(Exception $ex) {
        $pdo = null;
        echo servererr();
        die();
    }
}

//affiliation room
function affiliation_room($roomId, $privateId) {
    if(empty(roomId) or empty(privateId)) {
        return badreq();
    }

global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        $pdo->beginTransaction();
        try {
            $sql = ""
        }
}

//delete room
function delete_room($roomId, $privateId) {
    //need privateId
    if(empty($roomId) or empty($privateId)) {
        echo badreq();
        die();
    }

global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        //delete room information
        $pdo -> beginTransaction();
        try {
            $sql = "UPDATE room SET";
            $params = array ();
            $first = true;

            $sql = $sql." WHERE privateId = :privateId";
            $params[':privateId'] = $privateId;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pdo->commit();
            $pdo = null;
            if ($stmt->rowCount() == 0) {
                echo badreq();
                die();
            }
            return ok();

        } catch (Exception $e) {
            $pdo->rollBack();
            $pdo = null;
            echo servererr();
            die();
        }
    } catch (Exception $ex) {
        $pdo = null;
        echo servererr();
        die();
        }
    }
        

//change host
function change_host($roomId, $privateId, $userId) {
    if(empty($privateId)) {
        echo badreq();
        die();
    }
global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }
}

//grant permission
function grant_permission($roomId, $privateId, $userId) {
global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }
}

//change room
function change_room($privateId, $roomId, $name, $purpose) {
global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }
}

//delete member
function delete_member($roomId, $privateId, $userId) {
    global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }
}