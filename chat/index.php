<?php

require "../header.php";
require "../utility.php";

// QUERY KEY : update chat
$KEY_PRIVATE_ID = "privateId";
$KEY_ROOM_ID = "roomId";
$KEY_LAST_TIME = "lastTime";

// JSON KEY : send chat
# room ID : 
# private ID :
$KEY_CONTENT = "content";

// QUERY KEY : lock chat
# room ID
# private ID
$KEY_LOCK = "lock";

// DB SELECT RESULT KEY : update chat
$KEY_CHAT_ID = "chatId";
# room ID
$KEY_USER_ID = "userId";
# content
$KEY_CHAT_TIME = "chatTime";

// RETURN JSON KEY : update cht
$KEY_CHATS = "chats";
# last time : 


// Processing
$req = $_SERVER["REQUEST_METHOD"];
if ($req == "GET") {
    // update chat
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $lastTime = $_GET[$KEY_LAST_TIME];
    echo get_chat($privateId, $roomId, $lastTime);
    return;

} else if ($req == "POST") {
    // send chat
    $json_string = file_get_contents('php://input'); # Content-Type:application/json
    $json = json_decode($json_string, true);
    $privateId = $json[$KEY_PRIVATE_ID];
    $roomId = $json[$KEY_ROOM_ID];
    $content = $json[$KEY_CONTENT];
    echo send_chat($privateId, $roomId, $content);
    return;

} else if ($req == "PUT") {
    // lock chat
    $params = array ();
    parse_str(file_get_contents('php://input'), $params);
    $privateId = $params[$KEY_PRIVATE_ID];
    $roomId = (int)$params[$KEY_ROOM_ID];
    $lock = $params[$KEY_LOCK];
    $lock = $lock == "true" ? true : false;
    echo lock_chat($privateId, $roomId, $lock);
    return;

} else {
    echo badreq();
    die();

}


// functions
// update chat (get chat)
function get_chat($privateId, $roomId, $lastTime) {    
    // need private ID and room ID
    if (empty($privateId) or empty($roomId)) {
        echo badreq();
        die();
    }

    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if ($pdo == null) {
            echo servererr();
            die();
        }

        // Select chat
        // SQL
        $sql = "SELECT
            a.chatId, a.roomId, a.userId, a.content, a.chatTime
            FROM chat a, user b
            WHERE a.userId = b.userId
            AND a.roomId = :roomId AND b.privateId = :privateId
            AND a.chatTime >= :lastTime";
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':roomId' => $roomId,
            ':privateId' => $privateId,
            ':lastTime' => $lastTime
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $chats = array ();
        global $KEY_CHAT_ID, $KEY_ROOM_ID, $KEY_USER_ID, $KEY_CONTENT, $KEY_CHAT_TIME, $KEY_LAST_TIME, $TIME_FORMAT, $KEY_CHATS;
        foreach ($result as $val) {
            $chat = array (
                $KEY_CHAT_ID => (int)$val[$KEY_CHAT_ID],
                $KEY_ROOM_ID => (int)$val[$KEY_ROOM_ID],
                $KEY_USER_ID => (int)$val[$KEY_USER_ID],
                $KEY_CONTENT => $val[$KEY_CONTENT],
                $KEY_CHAT_TIME => $val[$KEY_CHAT_TIME]
            );
            $chats[] = $chat;
        }
        $lastTime = date($TIME_FORMAT);
        return json_encode(
            array (
                $KEY_CHATS => $chats,
                $KEY_LAST_TIME => $lastTime
            )
        );
        

    } catch (Exception $e) {
        $pdo = null;
        echo servererr();
        die();
    }

}

// send chat
function send_chat($roomId, $privateId, $content) {

}

// lock chat
function lock_chat($roomId, $privateId, $lock) {

}

?>