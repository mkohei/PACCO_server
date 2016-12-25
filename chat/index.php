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

}

// send chat
function send_chat($roomId, $privateId, $content) {

}

// lock chat
function lock_chat($roomId, $privateId, $lock) {

}

?>