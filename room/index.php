<?php

require "../header.php";
require "../utility.php";

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
$KEY_PERMISSION_DOC = "permissionDoc";
$KEY_PERMISSION_SUR = "permissionSur";
#roomId
#privateId
#userId
#request

//QUERY KEY : change room
#privateId
#roomId
#name
#purpose
#password
#isPublic
#request

//QUERY KEY : affiliation room
#roomId
#privateId
#request

$req = $_SERVER["REQUEST_METHOD"];
if($req == "POST") {
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $request = $json[$KEY_REQUEST];
    if($request == "CREATE") {
        //create room
        $name = $json[$KEY_NAME];
        $purpose = $json[$KEY_PURPOSE];
        $isPublic = $json[$KEY_IS_PUBLIC];
        $password = $json[$KEY_PASSWORD];
        $host = $json[$KEY_HOST];

        #$roomId = $json[$KEY_ROOM_ID];
        #echo create_room($name, $purpose, $isPublic, $password, $host, $roomId);

        echo create_room($name, $purpose, $isPublic, $password, $host);
        return;

    } else if($request == "AFFILIATE") {
        //affiliation room
        $roomId = $json[$KEY_ROOM_ID];
        $privateId = $json[$KEY_PRIVATE_ID];
        echo affiliation_room($roomId, $privateId);
        return;
    } else {
        echo badreq();
        die();
    }
} else if($req == "PUT") {
    $params = array ();
    parse_str(file_get_contents('php://input'), $params);
    $request = $params[$KEY_REQUEST];
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
        echo change_host($roomId, $privateId, $userId);
        return;

    } else if($request == "GRANT") {
        //grant permission
        $roomId = $params[$KEY_ROOM_ID];
        $privateId = $params[$KEY_PRIVATE_ID];
        $userId = $params[$KEY_USER_ID];
        $permissionDoc = $params[$KEY_PERMISSION_DOC];
        $permissionSur = $params[$KEY_PERMISSION_SUR];
        echo grant_permission($roomId, $privateId, $userId, $permissionDoc, $permissionSur);
        return;

    } else if($request == "CHANGE") {
        //change room
        $privateId = $params[$KEY_PRIVATE_ID];
        $roomId = $params[$KEY_ROOM_ID];
        $name = $params[$KEY_NAME];
        $purpose = $params[$KEY_PURPOSE];
        $password = $params[$KEY_PASSWORD];
        $isPublic = $params[$KEY_IS_PUBLIC];
        echo change_room($privateId, $roomId, $name, $purpose, $password, $isPublic);
        return;
    }

} else if ($req == "GET") {
    $request = $_GET["request"];
    if ($request == "ROOM") {
        // get room
        $privateId = $_GET["privateId"];
        echo get_room($privateId);
        return;

    } else if ($request == "MEMBER") {
        // get member
        $roomId = $_GET['roomId'];
        echo get_member($roomId);
        return;

    } else if ($request == "SEARCH") {
        // 現状 get all room

    } else {
        echo badreq();
        die();
    }

} else {
    //Error
    echo badreq();
    die();
}

//functions
//create room
function create_room($name, $purpose, $isPublic, $password, $host) {

#function create_room($name, $purpose, $isPublic, $password, $host, $roomId) {

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
            if (is_null($isPublic)) $isPublic = false;

            //SQL
            /*
            $sql = "INSERT INTO pacco.room
            (name, purpose, isPublic, password, host)
            VALUES 
            (:name, :purpose, :isPublic, :password, :host)";
            $params = array (

                ':name' => $name,
                ':purpose' => $purpose,
                ':isPublic' => $isPublic,
                ':password' => $password,
                ':host' => $host
            );*/
            
            #INSERT ROOM
            $sql = "INSERT INTO pacco.room
            (name, purpose, isPublic, host)
            VALUES 
            (:name, :purpose, :isPublic, :host)";
            $params = array (
                ':name' => $name,
                ':purpose' => $purpose,
                ':isPublic' => $isPublic,
                ':host' => $host
            );

            $stmt = $pdo->prepare($sql);
            $stmt -> execute($params);
            
            //INSERT AFFILIATION
            /*
            $aff_sql = "INSERT INTO affiliation (roomId, userId)
                    SELECT a.roomId, b.userId
                    FROM room a, user b
                    WHERE b.userId=a.host 
                    AND a.roomId=:roomId
                    AND a.host=:host";
            */
            
            $aff_sql = "INSERT INTO affiliation (roomId, userId)
                    VALUES ((SELECT last_insert_id()), :host)";
                          
            $aff_params = array (
                ':host' => $host
            );
            $stmt = $pdo->prepare($aff_sql);    
            $stmt->execute($aff_params);

            if($stmt->rowCount() == 0) {
                echo badreq();
                die();
            }

            $pdo -> commit();
            $pdo = null;
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
    if(empty($roomId) or empty($privateId)) {
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
            //SQL
            $sql = "INSERT INTO affiliation
                    (roomId, userId)
                    VALUES 
                    (:roomId, (SELECT userId FROM user WHERE privateId = :privateId))";
            //INSERT
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pdo->commit();
            $pdo = null;
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


//delete room
function delete_room($roomId, $privateId) {
    //need privateId
    if(empty($roomId) or empty($privateId)) {
        return badreq();
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
        //delete room
        try {
            //SETのもの(isDeleted)をUPDATEする
            //3行目は結合
            //4行目は自分がhostであることの証明
            //5行目は削除するルーム
            $sql = "UPDATE room a, user b
            SET a.isDeleted = true
            WHERE a.host = b.userId             
            AND b.privateId = :privateId
            AND a.roomId = :roomId";
            //array内で変換
            $params = array (
                'roomId' => $roomId,
                'privateId' => $privateId
            );
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
    if(empty($roomId) or empty($privateId) or empty($userId)) {
        return badreq();
    }

global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        $pdo -> beginTransaction();
        try{
            $sql = "UPDATE room a, user b
                    SET a.host = :userId
                    WHERE a.host = b.userId
                    AND a.roomId = :roomId
                    AND b.privateId = :privateId";
            $params = array (
                ':userId' => $userId,
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pdo->commit();
            if($stmt->rowCount() == 0) {
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

//grant permission
function grant_permission($roomId, $privateId, $userId, $permissionDoc, $permissionSur) {
    if(empty($roomId) or empty($privateId) or empty($userId)) {
        return badreq();
    }

    if(empty($permissionDoc) and empty($permissionSur)) {
        return badreq();
    }

global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        $pdo -> beginTransaction();
        try{
            $sql = "UPDATE affiliation a, room b, user c
                    SET a.hasPermissionDoc = :permissionDoc, a.hasPermissionSur = :permissionSur
                    WHERE a.roomId = b.roomId
                    AND b.host = c.userId
                    AND a.roomId = :roomId
                    AND a.userId = :userId
                    AND c.privateId = :privateId";
            $params = array (
                ':permissionDoc' => (bool)$permissionDoc,
                ':permissionSur' => (bool)$permissionSur,
                ':roomId' => $roomId,
                ':userId' => $userId,
                ':privateId' => $privateId
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pdo->commit();
            if ($stmt->rowCount() == 0) {
                echo badreq();
                die();
            }
            return ok();

        } catch (Exception $ex) {
            $pdo->rollBack();
            $pdo->null;
            echo servererr();
            die();
        }
    } catch (Exception $e) {
        $pdo = null;
        echo servererr();
        die();
    }
}

//change room
function change_room($privateId, $roomId, $name, $purpose, $password, $isPublic) {
    if(empty($privateId) or empty($roomId)) {
        return badreq();
    }
    global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        $pdo -> beginTransaction();
        try {
            $first = true;
            $sql = "UPDATE room a, user b SET ";
            $params = array ();
            if(!empty($name)) {
                $sql = $sql."a.name = :name";
                $first = false;
                $params[':name'] = $name;
            }
            if(!empty($purpose)) {
                if(!$first) $sql = $sql.", ";
                $sql = $sql."a.purpose = :purpose";
                $first = false;
                $params[':purpose'] = $purpose;
            }
            if(!empty($password)) {
                if(!$first) $sql = $sql.", ";
                $sql = $sql."a.password = :password";
                $first = false;
                $params[':password'] = $password;
            }
            if(!is_null($isPublic)) {
                if(!$first) $sql = $sql.", ";
                $sql = $sql."a.isPublic = :isPublic";
                $first = false;
                if ($isPublic == "true") $isPublic = true;
                else $isPublic = false;
                $params[':isPublic'] = (bool)$isPublic;
            }
            $sql = $sql." WHERE a.host = b.userId
                            AND a.roomId = :roomId
                            AND b.privateId = :privateId";
            //print $sql;
            pure_dump($params);
            $params[':roomId'] = $roomId;
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

        } catch (Exception $ex) {
            $pdo->rollBack();
            $pdo = null;
            echo servererr();
            die();
        }
    } catch (Exception $e) {
        $pdo = null;
        echo servererr();
        die();
    }
}

function get_room($privateId) {
    if (empty($privateId)) return badreq();
    global $DNS, $USER, $PW;
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) return servererr();

        $sql = "SELECT
            a.roomId, a.name, a.purpose, a.host
            FROM room a, affiliation b, user c
            WHERE a.roomId = b.roomId AND b.userId = c.userId
            AND c.privateId = :privateId";
        $params = array (
            ':privateId' => $privateId
        );
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        $pdo = null;
        //if (!$success) return badreq();
        $result = $stmt->fetchAll();
        $rooms = array ();
        foreach ($result as $val) {
            $r = array (
                'roomId' => (int)$val['roomId'],
                'name' => $val['name'],
                'purpose' => $val['purpose'],
                //'host' => (int)$val['host']
            );
            $rooms[] = $r;
        }
        return json_encode (
            array (
                'rooms' => $rooms
            )
        );
    } catch (Exception $e) {
        $pdo = null;
        return servererr();
    }
}


function get_member($roomId) {
    if (empty($roomId)) return badreq();
    global $DNS, $USER, $PW;
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) return servererr();

        $sql = "SELECT
            a.userId, a.name, b.affiliationId, b.roomId
            FROM user a, affiliation b
            WHERE a.userId = b.userId
            AND b.roomId = :roomId";
        $params = array (
            ':roomId' => $roomId
        );
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        $pdo = null;
        //if (!success) return badreq();
        $result = $stmt->fetchAll();
        $members = array ();
        $affiliations = array ();
        foreach ($result as $val) {
            $m = array (
                'userId' => (int)$val['userId'],
                'name' => $val['name']
            );
            $a = array (
                'affiliationId' => (int)$val['affiliation'],
                'userId' => (int)$val['userId'],
                'roomId' => (int)$val['roomId']
            );
            $members[] = $m;
            $affiliations[] = $a;
        }
        return json_encode (
            array (
                'members' => $members,
                'affiliations' => $affiliations
            )
        );

    } catch (Exception $e) {
        $pdo = null;
        return servererr();
    }
}
    
?>