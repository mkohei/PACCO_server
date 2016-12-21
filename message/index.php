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
$KEY_UESR_ID = "userId";
# content

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
            AND a.messageTime >= :lastTime";
        // SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':lastTime' => $lastTime
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;
        global $KEY_MESSAGE_ID, $KEY_TO_USER, $KEY_FROM_USER, $KEY_CONTENT, $KEY_MESSAGE_TIME, $KEY_LAST_TIME, $KEY_MESSAGES, $TIME_FORMAT;
        $messages = array();
        foreach ($result as $val) {
            $mes = array (
                $KEY_MESSAGE_ID => $val[$KEY_MESSAGE_ID],
                $KEY_TO_USER => $val[$KEY_TO_USER],
                $KEY_FROM_USER => $val[$KEY_FROM_USER],
                $KEY_CONTENT => $val[$KEY_CONTENT]
            );
            $messages[] = $mes;
        }
        $lastTime = date($TIME_FORMAT);
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
    
}

?>