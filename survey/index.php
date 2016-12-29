<?php

require "../header.php";
require "../utility.php";

// JSON KEY : create survey
$KEY_REQUEST_CREATE = "CREATESURVEY";
$KEY_ROOM_ID = "roomId";
$KEY_NAME = "name";
$KEY_CREATOR = "creator";
$KEY_DESCRIPTION = "description";
$KEY_QUESTIONS = "questions";
$KEY_QTEXT = "qText";
$KEY_ITEMS = "items";
$KEY_TEXT = "text";
$KEY_ITEMTYPE = "itemtype";

// QUERY KEY : list get
$KEY_REQUEST_SURVEY_LIST = "SURVEYLIST";
$KEY_PRIVATE = "privateId";    
# roomId :
$KEY_LAST_TIME = "lastTime";

// QUERY KEY : get survey 
$KEY_REQUEST_SURVEY = "SURVEY";
$KEY_PRIVATE_ID = "privateId";
# roomId :
$KEY_SURVEY_ID = "surveyId";
# lastTime :

// QUERY KEY : answer get 
$KEY_REQUEST_ANSWER_GET = "ANSWERGET";
# roomId :
# surveyId :
# privateId :
# lastTime :

// JSON KEY : answer survey
$KEY_REQUEST_ANSWER = "ANSWER";
# surveyId :
$KEY_ANSWERER = "answerer";
$KEY_Q_ID = "qId";
$KEY_ANSWER = "answer";


// RETURN JSON KEY : list get
# lastTime :
$KEY_SURVEY = "survey";
# surveyId :
$KEY_NAME = "name";
# creator :
# description :
$KEY_ANSWER_WANTED = "answerWanted";

// RETURN JSON KEY : get survey 
# lastTime :
$KEY_SURVEYS = "surveys";
# qId :
# qText :
# items :
$KEY_ITEM_ID = "itemId";
# text :
# itemType :

// RETURN JSON KEY : answer get 
# lastTime :
$KEY_ANSWERS = "answers";
$KEY_ANSWER_ID = "answerId";
# answerer :
$KEY_QUES_TIONS = "ques-tions";
# qId :
$KEY_ANSWER = "answer";


// processing
$req = $_SERVER["REQUEST_METHOD"];
if($req == "POST" and $KEY_REQUEST_CREATE){
    // create survey
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $roomId = $json[$KEY_ROOM];
    $name = $json[$KEY_NAME];
    $creator = $json[$KEY_CREATOR];
    $description = $json[$KEY_DESCRIPTION];
    $questions = $json[$KEY_QUESTIONS];
    $qText = $json[$KEY_QTEXT];
    $items = $json[$KEY_ITEMS];
    $text = $json[$KEY_TEXT];
    $itemtype = $json[$KEY_ITEMTYPE];
    echo create_survey($roomId, $name, $creator, $description, $questions, $qText, $items, $text, $itemtype);
    return;

} else if($req == "GET" and $KEY_REQUEST_SURVEY_LIST){
    // list get
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $lastTime = $_GET[$KEY_LAST_TIME];
    echo survey_list_get($privateId, $roomId, $lastTime);
    return;

} else if($req == "GET" and $KEY_REQUEST_SURVEY){
    // get survey 
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $surveyId = $_GET[$KEY_SURVEY_ID];
    $lastTime = $_GET[$KEY_LASTTIME];
    echo answer_get($privateId, $roomId, $surveyId, $lastTime);
    return;

} else if($req == "GET" and $KEY_REQUEST_ANSWER_GET){
    // answer get
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $surveyId = $_GET[$KEY_SURVEY_ID];
    $lastTime = $_GET[$KEY_LASTTIME];
    echo answer_survey($privateId, $roomId, $surveyId, $lastTime);
    return;

} else if($req == "POST" and $KEY_REQUEST_ANSWER){
    // answer survey
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $surveyId = $json[$KEY_SURVEY_ID];
    $answerer = $json[$kEY_ANSWERER];
    $qId = $json[$KEY_Q_ID];
    $answer = $json[$KEY_ANSWER];
    echo survey_answer($surveyId, $answerer, $answerer, $qId, $answer);
    return;

} else {
    echo badreq(); // Error 
}


// functions 
// create survey 
function create_survey($roomId, $name, $creator, $description, $questions, $qText, $items, $text, $itemtype) {
    // 
    if((empty($roomId) or empty($creator)) == true)
        return badreq();

    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            echo servererr();
            die();
        }    

        // Insert new survey
        $pdo->beginTransaction();
        try {
            $sql = "INSERT INTO pacco.survey 
                    (surveyCreate, roomId, name, description, question, qText, items, text, itemtype);
                    VALUES
                    (:surveyCreate, :roomId, :name, :description, :question, :qText, :items, :text, :itemtype)";
                
            // INSERT
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':surveyCreate' => $surveyCreate, 
                ':roomId' => $roomId, 
                ':name' => $name, 
                ':creator' => $creator, 
                ':description' => $description, 
                ':questions' => $questions,
                'qText' => $qText, 
                'items' => $items, 
                'text' => $text, 
                'itemtype' => $itemtype, 
            );
            $stmt = execute($params);
            $pdo->commit();
            $pdo = null;
            if ($stmt->rowCount == 0) {
                echo badreq();
                die();                
            }
            return ok();

        } catch (Exception $e) {
            $pdo->rollBack(); // reset 
            echo servererr();
            die();
        }


// list get
function list_get($privateId, $roomId, $lastTime){

}

// get survey
function get_survey($privateId, $roomId, $surveyId, $lastTime){

}

// answer get
function answer_get($privateId, $roomId, $surveyId, $lastTime){

}

// answer survey
function answer_survey($surveyId, $answerer, $questions, $qId, $answer) {

    if(empty($surveyId) or empty($answerer) or empty($questions) == true)
        return badreq();

    global $DNS, $USER, $PW;
 
    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            echo servererr();
            die();
        }

        $pdo->beginTransaction();
        try {
            // INSERT
            $sql = "a";
            // INSERT
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':surveyId' => $privateId,
                ':answerer' => $answerer,
                ':questions' => $questions,
                ':qId' => $qId,
                ':answer' => $answer
            );
            $stmt->execute($params);
            $pdo->commit();
        } catch (Exception $e){
            $pdo->rollBack();
            echo servererr();
            die();
        }


        
        }

    
 
?>