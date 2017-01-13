<?php

require "../header.php";
require "../utility.php";


// JSON KEY : create survey
$KEY_ROOM_ID = "roomId";
$KEY_NAME = "name";
$KEY_CREATOR = "creator";
$KEY_DESCRIPTION = "description";
$KEY_QUESTIONS = "questions";
$KEY_Q_TEXT = "qText";
$KEY_ITEMS = "items";
$KEY_TEXT = "text";


// JSON KEY : answer survey
$KEY_SURVEY_ID = "surveyId";
$KEY_ANSWERER = "answerer";
$KEY_Q_ID = "qId";
$KEY_ANSWER = "answer";


// QUERY KEY : list get
$KEY_PRIVATE_ID = "privateId";    
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
# privateId :
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

//REQUEST
$KEY_REQUEST = "request";


// processing
$req = $_SERVER["REQUEST_METHOD"];
// POST
if($req == "POST"){
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
        return badreq();
    }
}

// GET
if($req == "GET") {
    $request = $_GET[$KEY_REQUEST];
    
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

//functions
//create survey
function create_survey($json) { 
    if((empty($roomId) or empty($name) or empty($creator))  == true) {
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
            // 
            $roomId = $json[$KEY_ROOM_ID];
            $privateId = $json[$KEY_CREATOR];
            
            // surveyをcreateする条件を満たしているか
            $sql = "SELECT b.userId FROM room a, affiliation b, user c, user d 
                WHERE a.host = c.userId
                AND a.roomId = b.roomId
                AND b.userId = d.userId
                AND d.privateId = :privateId
                AND (c.privateId = :privateId OR c.hasPermissionSur = true)";

            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            $userId = (int)$result[0]["roomId"];

            if(empty($userId)){
                return badreq();
            }

            //INSERT
            // survey
            $name = $json[$KEY_NAME];
            $description = $json[$KEY_DESCRIPTION];
            $sql = "INSERT INTO survey (roomId, creator, name, description)
                VALUES (:roomId, :creator, :name, :description)";

            $stmt = $pdo->prepare($sql);
            $pdo->prepare($sql);
            $params = array (
                ':roomId' => $roomId,
                ':creator' => $userId,
                ':name' => $name,
                ':description' => $description
            );
            $pdo->execute($params);
            
            // survey question
            $sql = "SELECT last_insert_id()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $surveyId = intval($result[0][0]);
            $questions = $json[$KEY_QUESTIONS];

            foreach($questions as $key => $val) {
                $qText = $val[$KEY_Q_TEXT];
                $type = $val[$KEY_TYPE];
                $sql = "INSERT INTO survey_question (surveyId, qText, type)
                    VALUES (:surveyId, :qText, :type)";
                $pdo->prepare($sql);
                $params = array (
                    ':surveyId' => $surveyId,
                    ':qText' => $qText,
                    ':type' => $type
                );
                $stmt->execute($params);
                
                // survey item
                $sql = "SELECT last_insert_id()";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll();
                $qId = intval($result[0][0]);
                $items = $val[$KEY_ITEMS];
                foreach($items as $key => $item) {
                    $text = $item[$KEY_TEXT];
                    $sql = "INSERT INTO survey_item (qId, text)
                    VALUES (:qId, :text)";
                    $pdo->prepare($sql);
                    $params = array (
                        ':qId' => $qId,
                        ':text' => $text
                    );
                    $pdo->execute($params);
                }
                
            }
                        
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
    if(empty($surveyId) or empty($answerer) == true){
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
            // answer_list
            $roomId = $json[$KEY_ROOM_ID];
            $privateId = $json[$KEY_ANSWERER];

            // roomId, privateIdの整合性
            $sql = "SELECT b.userId FROM room a, affiliation b, user c
            WHERE a.roomId = b.roomId
            AND b.userId = c.userId
            AND (a.roomId = :roomId OR c.privateId = :privateId)";
                
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );

            $stmt = $pdo->prepare($sql);
            $stmt = execute($params);
            $result = $stmt->fetchAll();
            $userId = (int)$result[0]["roomId"];
            if($num != 1){
                return badreq();
            }

            $surveyId = $json[$KEY_SURVEY_ID];
            // INSERT
            $sql = "INSERT INTO answer_list
            (surveyId, answerer)
            VALUES
            (:surveyId, :answerer)";

            $stmt = $pdo->prepare($sql);
            $params = array (
                ':surveyId' => $surveyId, 
                ':answerer' => $userId
            );

            $stmt->execute($params);

            // answer
            $sql = "SELECT last_insert_id()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $qId = intval($result[0][0]);
            $answers = $json[$KEY_ANSWERS];
            foreach($answers as $key => $ans) {
               $answer = $ans[$KEY_ANSWER];
               $sql = "INSERT INTO answer (qId, answer)
               VALUES (:qId, :answer)";
               $stmt = $pdo->prepare($sql);
               $params = array (
                   ':qId' => $qId, 
                   ':answer' => $answer
               );
               $stmt->execute($params);
            }

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


// get survey list 
function get_survey_list($privateId, $roomId){
    if (empty($privateId) or empty($roomId) == true){
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

        global $KEY_SURVEYS, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED;
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
        // $lastTime = date($TIME_FORMAT);

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
function get_survey($privateId, $roomId, $surveyId) {
   
    if (empty($privateId) or empty($roomId) or empty($surveyId) == true){
        return badreq();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        // SQL(GET) 
        // surveys     
        $sql = "SELECT 
        a.qId, a.qText
        FROM survey_question a, affiliation b, user c
        WHERE b.userId = c.userId
        AND roomId = :roomId
        AND c.privateId = :privateId
        ";

        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId 
        );
        $stmt->execute($params);
        

        // items
        $sql = "SELECT last_insert_id()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $itemId = intval($result[0][0]);
        $items = $val[$KEY_ITEMS];

        foreach($items as $key => $item) {
            $text = $item[$KEY_TEXT];
            
            $sql = "SELECT
            a.itemId, a.text
            FROM survey_item a, affiliation b, user c
            WHERE b.userId = c.userId
            AND c.privateId = :privateId
            AND roomId = :roomId
            ";

            $stmt = $pdo->prepare($sql);
            $params = array (
                ':roomId' => $roomId, 
                ':privateId' => $privateId
            );
        }
            $stmt->execute($params);

            $result = $stmt->fetchAll();
            $pdo = null;
        

        global $KEY_SURVEY, $KEY_SURVEYS, $KEY_ROOM_ID, $KEY_Q_ID, $KEY_Q_TEXT, $KEY_ITEM_ID, $KEY_TEXT;
        $surveyget = array();

        foreach($result as $val){
            $sur = array (
                $KEY_ROOM_ID => (int)$val[$KEY_SURVEY_ID],
                $KEY_Q_ID => (int)$val[$KEY_Q_ID],
                $KEY_Q_TEXT => $val[$KEY_Q_TEXT],                  
                $KEY_SURVEYS => array (
                    $KEY_ITEM_ID = (int)$val[$KEY_ITEM_ID], 
                    $KEY_TEXT = (int)$val[$KEY_TEXT], 
                )   
            );
            $surveyget[] = $sur;
        }
        // $lastTime = date($TIME_FORMAT);

        return json_encode(
            array (
                $KEY_SURVEY => $sur
               // $KEY_LAST_TIME => $lastTime
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
    if (empty($privateId) or empty($roomId) or empty($surveyId) == true){
        return badreq();
    }
    // answers

    global $DNS, $USER, $PW;
    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        // SQL(GET)
        $sql = "SELECT
        a.answerId, a.answerer
        FROM a.answerlist, b.answer, c.affiliation, d.user
        WHERE a.answerer = c.userId
        AND c.userId = d.userId
        AND a.answerId = b.answerId
        AND (d.privateId = :privateId OR roomId = :roomId)
         ";

        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':privateId' => $privateId, 
            ':roomId' => $roomId
        );

        $stmt->execute($params);

        // ques-tions
        $sql = "SELECT last_insert_id()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        global $KEY_ANSWER_ID, $KEY_ANSWERER, $KEY_ROOM_ID, $KEY_Q_ID, $KEY_ANSWER, $KEY_QUESTIONS;
        
        $answerget = array();

        foreach($result as $val){
            $ans = array (
                $KEY_ANSWER_ID => (int)$val[$KEY_ANSWER_ID],
                $KEY_ANSWERER => (int)$val[$KEY_ANSWERER], 
                $KEY_ROOM_ID => (int)$val[$KEY_ROOM_ID], 
                $KEY_QUESTIONS => array (
                    $KEY_Q_ID => (int)$val[$KEY_Q_ID], 
                    $KEY_ANSWER => $val[$KEY_ANSWER], 
                )
            );
            $answerget[] = $ans;
        }
        // $lastTime = date($TIME_FORMAT);

        return json_encode(
            array (
                $KEY_ANSWER => $ans
                // $KEY_LAST_TIME => $lastTime
            )
        );
    } catch(Exception $e){
        $pdo = null;
        echo servererr();
        die();
    }
}
 
?>
