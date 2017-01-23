<?php

require "../header.php";
require "../utility.php";

// QUERY KEY : update message
$KEY_PRIVATE_ID = "privateId";
$KEY_ROOM_ID = "roomId";
$KEY_LAST_TIME = "lastTime";

// JSON KEY : send message
# room ID
# private ID
$KEY_USER_ID = "userId";
# content

// QUERY KEY : lock message
# room ID
# private ID
$KEY_LOCK = "lock";

// DB SELECT RESULT KEY : update message
$KEY_MESSAGE_ID = "messageId";
# room ID : 
$KEY_FROM_USER = "fromUser";
$KEY_TO_USER = "toUser";
$KEY_CONTENT = "content";
$KEY_MESSAGE_TIME = "messageTime";

// RETURN JSON KEY : update message
# DB SELECT RESULT と同様 + 
# last time : 
$KEY_MESSAGES = "messages";


// processing
$req = $_SERVER["REQUEST_METHOD"];
if ($req == "GET") {
    // update message
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $lastTime = $_GET[$KEY_LAST_TIME];
    echo get_message($privateId, $roomId, $lastTime);
    return;

} else if ($req == "POST") {
    // send message
    $json_string = file_get_contents('php://input'); # Content-Type:application/json
    $json = json_decode($json_string, true);
    $roomId = $json[$KEY_ROOM_ID];
    $privateId = $json[$KEY_PRIVATE_ID];
    $userId = $json[$KEY_USER_ID];
    $content = $json[$KEY_CONTENT];
    echo send_message($roomId, $privateId, $userId, $content);
    return;

} else if ($req == "PUT") {
    // lock message
    $params = array ();
    parse_str(file_get_contents('php://input'), $params);
    $roomId = (int)$params[$KEY_ROOM_ID];
    $privateId = $params[$KEY_PRIVATE_ID];
    $lock = $params[$KEY_LOCK];
    $lock = $lock == "true" ? true : false;
    echo lock_message($roomId, $privateId, $lock);
    return;

} else {
    echo badreq();
    die();
}


// functions
// update message (get message)
function get_message($privateId, $roomId, $lastTime) {
    // need private ID and room ID
    if (empty($privateId) or empty($roomId)) {
        echo badreq();
        die();
    }

    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        // Select message
        // SQL
        $sql = "SELECT 
            a.messageId, a.roomId, a.fromUser, a.toUser, a.content, a.messageTime
            FROM message a, user b, user c
            WHERE a.fromUser = b.userId
            AND a.toUser = c.userId
            AND (b.privateId = :privateId OR c.privateId = :privateId)
            AND roomId = :roomId
            AND a.messageTime >= :lastTime";
        // SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId,
            ':lastTime' => $lastTime
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;
        global $KEY_MESSAGE_ID, $KEY_TO_USER, $KEY_FROM_USER, $KEY_ROOM_ID, $KEY_CONTENT, $KEY_MESSAGE_TIME, $KEY_LAST_TIME, $KEY_MESSAGES, $TIME_FORMAT;
        $messages = array();
        foreach ($result as $val) {
            $mes = array (
                $KEY_MESSAGE_ID => (int)$val[$KEY_MESSAGE_ID],
                $KEY_TO_USER => (int)$val[$KEY_TO_USER],
                $KEY_FROM_USER => (int)$val[$KEY_FROM_USER],
                $KEY_ROOM_ID => (int)$val[$KEY_ROOM_ID],
                $KEY_CONTENT => $val[$KEY_CONTENT],
                $KEY_MESSAGE_TIME => $val[$KEY_MESSAGE_TIME]
            );
            $messages[] = $mes;
        }
        #$lastTime = date($TIME_FORMAT);
        return json_encode(
            array (
                $KEY_MESSAGES => $messages,
                $KEY_LAST_TIME => $lastTime
            )
        );

    } catch (Exception $e) {
        $pdo = null;
        echo servererr();
        die();
    }
}


// send message
function send_message($roomId, $privateId, $userId, $content) {
    if (empty($roomId) or empty($privateId) or empty($userId)) {
        return badreq();
    }

    global $DNS, $USER, $PW;
    echo 'send massage funcation is called.';
    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if ($pdo == null) {
            echo servererr();
            die();
        }

        // Insert message
        // SQL
        $pdo->beginTransaction();
        try {
            // roomId, privateIdの整合性（所属しているか）とmessageロックの確認
            $sql = "SELECT COUNT(*) AS num FROM room a, affiliation b, user c
                WHERE a.roomId = b.roomId AND b.userId = c.userId
                AND a.roomId = :roomId AND c.privateId = :privateId
                AND a.messageIsLocked = false";
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            $num = (int)$result[0]['num'];
            pure_dump($result);
            if ($num >= 1) {
                echo badreq();
                die();
            }

            $sql = "INSERT INTO message
                (roomId, fromUser, toUser, content)
                VALUES (:roomId, (SELECT userId FROM user WHERE privateId = :privateId), :toUser, :content)";
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId,
                ':toUser' => $userId,
                ':content' => $content
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


// lock message
function lock_message($roomId, $privateId, $lock) {
    if (empty($roomId) or empty($privateId) or is_null($lock)) 
        badreq();

    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if ($pdo == null) {
            echo servererr();
            die();
        }

        // Put lock message
        $pdo->beginTransaction();
        try {
            $sql = "UPDATE room a, user b
                SET a.messageIsLocked = :lock
                WHERE a.host = b.userId
                AND a.roomId = :roomId
                AND b.privateId = :privateId";
            $params = array (
                ':lock' => $lock,
                ':roomId' => $roomId,
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

        } catch (Exceotion $ex) {
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

?>
