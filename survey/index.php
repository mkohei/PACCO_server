<?php

require "../header.php";
require "../utility.php";


// JSON KEY : create survey
$KEY_ROOM_ID = "roomId";
$KEY_NAME = "name";
$KEY_CREATOR = "creator";
$KEY_DESCRIPTION = "description";
$KEY_QUESTIONS = "questions";
$KEY_QTEXT = "qText";
$KEY_ITEMS = "items";
$KEY_TEXT = "text";
$KEY_ITEMTYPE = "itemtype";


// JSON KEY : answer survey
$KEY_SURVEY_ID = "surveyId";
$KEY_ANSWERER = "answerer";
$KEY_Q_ID = "qId";
$KEY_ANSWER = "answer";


// QUERY KEY : list get
$KEY_PRIVATE = "privateId";    
# roomId :
$KEY_LAST_TIME = "lastTime";

// RETURN JSON KEY : list get
# lastTime :
# surveyId :
$KEY_NAME = "name";
# creator :
# description :
$KEY_ANSWER_WANTED = "answerWanted";


// QUERY KEY : get survey 
$KEY_PRIVATE_ID = "privateId";
# roomId :
# surveyId :
# lastTime :

// RETURN JSON KEY : get survey 
# lastTime :
$KEY_SURVEYS = "surveys";
# qId :
# qText :
# items :
$KEY_ITEM_ID = "itemId";
# text :
# itemType :


// QUERY KEY : answer get 
# roomId :
# surveyId :
# privateId :
# lastTime :

// RETURN JSON KEY : answer get 
# lastTime :
$KEY_ANSWERS = "answers";
$KEY_ANSWER_ID = "answerId";
# answerer :
# questions :
# qId :
$KEY_ANSWER = "answer";

// request
$KEY_REQUEST = "request";


// processing
$req = $_SERVER["REQUEST_METHOD"];

// POST
if($req == "POST"){
    // create survey
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $request = $json[$KEY_REQUEST];

    if ($request == "SURVEY") {
        // create survey 
        create_survey($json);
        return;

    } else if ($request == "ANSWER") {
        // answer survey 
        answer_survey($json);
        return;

    } else {
        // badreq
        return badreq();
    }
}

// GET
if($req == "GET") {
    if($request == "SURVEY_LIST") {
    // get survey list
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $lastTime = $_GET[$KEY_LAST_TIME];
    echo get_survey_list($privateId, $roomId, $lastTime);
    return;

    } else if($request == "SURVEY") {
        // get survey 
        $privateId = $_GET[$KEY_PRIVATE_ID];
        $roomId = $_GET[$KEY_ROOM_ID];
        $surveyId = $_GET[$KEY_SURVEY_ID];
        $lastTime = $_GET[$KEY_LASTTIME];
        echo get_survey($privateId, $roomId, $surveyId, $lastTime);
        return;

    } else if($request == "ANSWER") {
        // answer get
        $privateId = $_GET[$KEY_PRIVATE_ID];
        $roomId = $_GET[$KEY_ROOM_ID];
        $surveyId = $_GET[$KEY_SURVEY_ID];
        $lastTime = $_GET[$KEY_LASTTIME];
        echo answer_get($privateId, $roomId, $surveyId, $lastTime);
        return;

    } else {
        return badreq();  
    }
}


// functions 
// create survey 
function create_survey($json) { 
    if((empty($roomId) or empty($name) or empty($creator))  == true ) {
        return badreq();
    }

    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }    

        // Insert new survey
        $pdo->beginTransaction();
        try {
            // surveyをcreateする条件を満たしているか
            $sql = "SELECT COUNT(*) FROM room a, affiliation b, user c, user d 
                WHERE a.host = c.userId
                AND a.roomId = b.roomId
                AND b.userId = d.userId
                AND d.privateId = :privateId
                AND (c.privateId = :privateId OR hasPermissionSur = true)";

            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            $num = (int)$result[0][0];

            if($num != 1){
                echo badreq();
                die();
            }

            //INSERT
            $sql = "INSERT INTO pacco.survey
            (roomId, name, creator, description, questions, qText, items, text, itemtype)
            VALUES
            (:roomId, :name, (SELECT userId FROM room WHERE $privateId = :privateId), questions, qText, items, text, itemtype)";

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
                'itemtype' => $itemtype 
            );

            $stmt = execute($params);
            $pdo->commit();
            $pdo = null;
            return ok();
            
        } catch (Exception $ex){
           $pdo->rollBack();
           $pdo= null;
           return servererr();
        }

    } catch (Exception $e) {
        $pdo->rollBack(); // reset 
        echo servererr();
        die();
    }
}


// answer survey
function answer_survey($json) {
    if((empty($surveyId) or empty($answerer) or empty($qId)or empty($answer)) == true) {
        return badreq();
    }

    global $DNS, $USER, $PW;
    
    try {
        $pdo = new PDO($DNS, $USER, $PW);
           if($pdo == null){
               return servererr();
            }
        
        $pdo->beginTransaction();
        try {
            // roomId, privateIdの整合性
            $sql = "SELECT COUNT(*) AS num FROM room a, affiliation b, user c
            WHERE a.roomId = b.roomId
            AND b.userId = c.userId
            AND a.roomId = :roomId OR c.privateId = :privateId";
                
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );

            $stmt = $pdo->prepare($sql);
            $stmt = execute($params);
            $result = $stmt->fetchAll();
            $num = (int)$result[0]['num'];
            if($num != 1){
                return badreq();
            }

            // INSERT
            $sql = "INSERT INTO answer
            (surveyId, answerer, qId, answer)
            VALUES
            (:surveyId, :answerer, :qId, :answer)";

            $stmt = $pdo->prepare($sql); 
            $stmt->execute($params);
            $pdo->commit();
            $pdo = null;
            return ok();

        } catch (Exception $ex) {
            $pdo->rollBack();
            echo servererr();
            die();
        }
    } catch (Exception $e){
        $pdo = null;
        echo servererr();
        die();
    }
} 


// list get
function get_survey_list($privateId, $roomId, $lastTime){
    if (empty($privateId) or empty($roomId)){
        return badreq();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        // SQL
        $sql = "SELECT 
        a.surveyId, a.name, a.creator, a.description, a.answerWanted 
        FROM survey a, affiliation b, user c
        WHERE roomId = :roomId 
        AND c.privateId = :privateId
        AND b.userId = c.userId
        AND a.answerWanted = true";

        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId,
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_LAST_TIME, $KEY_SURVEYS, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED, $TIME_FORMAT;
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_SURVEY_ID => (int)$val[$KEY_SURVEY_ID],
                $KEY_NAME => (int)$val[$KEY_NAME],
                $KEY_CREATOR => (int)$val[$KEY_CREATOR],
                $KEY_DESCRIPTION => $val[$KEY_DESCRIPTION],
                $KEY_ANSWER_WANTED => $val[$KEY_ANSWER_WANTED]
            );
            $survey[] = $sur;
        }
        $lastTime = date($TIME_FORMAT);

        return json_encode(
            array (
                $KEY_SURVEY => $survey,
                $KEY_LAST_TIME => $lastTime
            )
        );

     } catch (Exception $e){
        $pdo = null;
        return servererr();
    }
}


// get survey
function get_survey($privateId, $roomId, $surveyId, $lastTime) {
   
    if (empty($privateId) or empty($roomId)){
        return badreq();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        // SQL(GET)      
        $sql = "SELECT 
        b.qId, b.qText, c.itemId, c.text, c.itemType
        FROM survey a, survey_question b, survey_item c, survey_type d, affiliation e, user f
        WHERE  a.surveyId = b.qId
        AND a.surveyId = c.qId 
        AND c.itemType = d.typeId
        AND e.userId = f.userId
        AND roomId = :roomId
        AND f.privateId = :privateId
        ";

        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId 
        );

        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_SURVEYS, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED,$KEY_LAST_TIME, $TIME_FORMAT;
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_SURVEY_ID => (int)$val[$KEY_SURVEY_ID],
                $KEY_NAME => (int)$val[$KEY_NAME],
                $KEY_CREATOR => (int)$val[$KEY_CREATOR],
                $KEY_DESCRIPTION => $val[$KEY_DESCRIPTION],
                $KEY_ANSWER_WANTED => $val[$KEY_ANSWER_WANTED]
            );
            $survey[] = $sur;
        }
        $lastTime = date($TIME_FORMAT);

        return json_encode(
            array (
                $KEY_SURVEY => $survey,
                $KEY_LAST_TIME => $lastTime
            )
        );
    } catch(Exception $e){
        $pdo = null;
        echo servererr();
        die();
    }
}


// answer get
function answer_get($privateId, $roomId, $surveyId, $lastTime){

    if (empty($privateId) or empty($roomId) or empty($surveyId) ){
        return badreq();
    }

    global $DNS, $USER, $PW;

    try{

        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        // SQL(GET)
        $sql = "SELECT
        a.answerId, a.answerer, b.qId, b.answer
        FROM a.answer_list, b.answer, c.survey_question, d.survey_item, e.affiliation, f.user        
        WHERE a.answerer = e.userId
        AND e.userId = f.userId
        AND b.qId = c.qId
        AND b.qId = d.qId 
        AND a.answerId = b.answerId
        AND (f.privateId = :privateId OR roomId = :roomId)
         ";

        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId, 
            ':roomId' => $roomId
        );

        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_LAST_TIME, $KEY_ANSWERS, $KEY_ANSWER_ID, $KEY_ANSWERER, $KEY_QUESTIONS, $KEY_Q_ID, $KEY_ANSWER, $TIME_FORMAT;
        
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_ANSWER_ID => (int)$val[$KEY_ANSWER_ID],
                $KEY_ANSWERER => (int)$val[$KEY_ANSWERER],
                $KEY_Q_ID => (int)$val[$KEY_Q_ID],
                $KEY_ANSWER => $val[$KEY_ANSWER],
                $KEY_ANSWER_WANTED => $val[$KEY_ANSWER_WANTED]
            );
            $survey[] = $sur;
        }
        $lastTime = date($TIME_FORMAT);

        return json_encode(
            array (
                $KEY_SURVEY => $survey,
                $KEY_LAST_TIME => $lastTime
            )
        );
    } catch(Exception $e){
        $pdo = null;
        echo servererr();
        die();
    }
}
 
?>