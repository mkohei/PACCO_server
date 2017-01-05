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
    echo list_get($privateId, $roomId, $lastTime);
    return;

} else if($req == "GET" and $KEY_REQUEST_SURVEY){
    // get survey 
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $surveyId = $_GET[$KEY_SURVEY_ID];
    $lastTime = $_GET[$KEY_LASTTIME];
    echo get_survey($privateId, $roomId, $surveyId, $lastTime);
    return;

} else if($req == "GET" and $KEY_REQUEST_ANSWER_GET){
    // answer get
    $privateId = $_GET[$KEY_PRIVATE_ID];
    $roomId = $_GET[$KEY_ROOM_ID];
    $surveyId = $_GET[$KEY_SURVEY_ID];
    $lastTime = $_GET[$KEY_LASTTIME];
    echo answer_get($privateId, $roomId, $surveyId, $lastTime);
    return;

} else if($req == "POST" and $KEY_REQUEST_ANSWER){
    // answer survey
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $surveyId = $json[$KEY_SURVEY_ID];
    $answerer = $json[$kEY_ANSWERER];
    $qId = $json[$KEY_Q_ID];
    $answer = $json[$KEY_ANSWER];
    echo answer_survey($surveyId, $answerer, $answerer, $qId, $answer);
    return;

} else {
    echo badreq(); 
    die();
}


// functions 
// create survey 
function create_survey($roomId, $name, $creator, $description, $questions, $qText, $items, $text, $itemtype) {
    // 
    if(empty($roomId) or empty($creator))
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
            // surveyをcreateする条件を満たしているか
            $sql = "SELECT COUNT(*) FROM room a, affiliation b, survey c 
                WHERE a.host.privateId = :privateId
                AND a.roomId = c.roomId 
                AND b.roomId = c.roomId
                AND hasPermissionSur = true";

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
            if ($stmt->rowCount() == 0) {
                echo badreq();
                die();                
            }
            
        } catch (Exception $e){
           $pdo->rollBack();
           $pdo= null;
           echo servererr();
           die();
        }

    } catch (Exception $ex) {
        $pdo->rollBack(); // reset 
        echo servererr();
        die();
    }
}


// list get
function list_get($privateId, $roomId, $lastTime){
    if (empty($privateId) or empty($roomId)){
        echo badreq();
        die();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            echo servererr();
            die();
        }

        // SQL
        $sql = "SELECT 
        a.privateId, 
        FROM 
        "

        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId,
            ':lastTime' => $lastTime
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_LAST_TIME, $KEY_SURVEY, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED, $TIME_FORMAT;
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_LAST_TIME => $val[$KEY_LAST_TIME],
                $KEY_SURVEY => (int)$val[$KEY_SURVEY],
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
        echo servererr();
        die();
    }
}

// get survey
function get_survey($privateId, $roomId, $surveyId, $lastTime) {
    if (empty($privateId) or empty($roomId)){
        echo badreq();
        die();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            echo servererr();
            die();
        }

        // SQL
        //SELECT
        $sql = "SELECT 
        a.surveyId, a.roomId, a.(SELECT userId FROM user), a.name, a.description, a.answerWanted, a.surveyTime
        FROM survey a, affiliation b, room c
        WHERE a.roomId =　  
        AND 
        "

        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId,
            ':lastTime' => $lastTime
        );

        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_LAST_TIME, $KEY_SURVEY, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED, $TIME_FORMAT;
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_LAST_TIME => $val[$KEY_LAST_TIME],
                $KEY_SURVEY => (int)$val[$KEY_SURVEY],
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
        echo badreq();
        die();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            echo servererr();
            die();
        }

        // SQL
        $sql = "SELECT
        a.privateId, 
        FROM 
         "

        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId, 
            ':roomId' => $roomId,
            ':lastTime' => $lastTime
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_LAST_TIME, $KEY_SURVEY, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED, $TIME_FORMAT;
        $survey = array();

        foreach($result as $val){
            $sur = array (
                $KEY_LAST_TIME => $val[$KEY_LAST_TIME],
                $KEY_SURVEY => (int)$val[$KEY_SURVEY],
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
            $sql = "INSERT";
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
        } catch (Exception $ex) {
            $pdo->rollBack();
            echo servererr();
            die();
      } catch (Exception $e){
          $pdo = null;
          echo servererr();
          die();
      }
}   
 
?>