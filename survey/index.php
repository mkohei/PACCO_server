<?php

require "../header.php";
require "../utility.php";


// JSON KEY : create survey
$KEY_REQUEST = "request";

$KEY_ROOM_ID = "roomId";
$KEY_NAME = "name";
$KEY_CREATOR = "creator";
$KEY_DESCRIPTION = "description";
$KEY_Q_TEXT = "qText";
$KEY_TYPE = "type";
$KEY_TEXT = "text";
$KEY_QUESTIONS = "questions";
$KEY_ITEMS = "items";

// JSON KEY : answer survey
$KEY_SURVEY_ID = "surveyId";
$KEY_ANSWERER = "answerer";
$KEY_Q_ID = "qId";
$KEY_ANSWER_LIST_ID = "answerListId";
$KEY_ANSWERS = "ANSWERS";
$KEY_ANSWER = "answer";


// QUERY KEY : list get
$KEY_PRIVATE_ID = "privateId";
# roomId :
$KEY_LAST_TIME = "lastTime";

// RETURN JSON KEY : list get
# lastTime :
# surveyId :
# name :
# creator :
# description :
$KEY_ANSWER_WANTED = "answerWanted";
$KEY_SURVEYS = "surveys";

// QUERY KEY : get survey
# privateId :
# roomId :
# surveyId :
# lastTime :

// RETURN JSON KEY : get survey
# lastTime :
# qId :
# qText :
# items :
$KEY_ITEM_ID = "itemId";
# text :
# itemType :
$KEY_SURVEY = "survey";
$KEY_ITEMS = "items";



// QUERY KEY : answer get
# roomId :
# surveyId :
# privateId :
# lastTime :

// RETURN JSON KEY : answer get
# lastTime :
# questions :
$KEY_ANSWERS = "answers";
$KEY_ANSWER_ID = "answerId";
# answerer :
# questions :
# qId :
# answer :
$KEY_ANSWER_LIST = "answerlist";


$req = $_SERVER["REQUEST_METHOD"];
if($req == "POST"){
    $json_string = file_get_contents('php://input');
    $json = json_decode($json_string, true);
    $request = $json[$KEY_REQUEST];

    if ($request == "CREATE") {
        echo create_survey($json);
        return;

    } else if ($request == "ANSWER") {
        echo answer_survey($json);
        return;

    } else {
        return badreq();
    }
} else if($req == "GET") {
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
        $lastTime = $_GET[$KEY_LAST_TIME];
        echo get_survey($privateId, $roomId, $surveyId, $lastTime);
        return;

    } else if($request == "ANSWER") {
        // answer get
        $privateId = $_GET[$KEY_PRIVATE_ID];
        $roomId = $_GET[$KEY_ROOM_ID];
        $surveyId = $_GET[$KEY_SURVEY_ID];
        $lastTime = $_GET[$KEY_LAST_TIME];
        echo answer_get($privateId, $roomId, $surveyId, $lastTime);
        return;
    }
} else {
    return badreq();
}

//functions
//create survey
function create_survey($json) {
    global $DNS, $USER, $PW;
    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }
        // Insert new survey
        $pdo -> beginTransaction();
        try {
            /*
            // surveyをcreateする条件を満たしているか
             $sql = "SELECT b.userId FROM room a, affiliation b, user c, user d
                WHERE a.host = c.userId
                AND a.roomId = b.roomId
                AND b.userId = d.userId 
                AND b.roomId = :roomId
                AND c.userId = :userId
                OR (d.userId = :userId AND b.hasPermissionSur = true)";
                /*
                AND c.privateId = (SELECT c.privateId FROM user c WHERE c.userId = :userId)
                OR  (d.privateId = (SELECT d.privateId FROM user d WHERE d.userId = :userId)
                AND b.hasPermissionSur = true)";
                
                AND c.privateId = :privateId
                OR (d.privateId = :privateId AND b.hasPermissionSur = true)";
                
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':roomId' => $roomId,
                ':userId' => $userId
                #':privateId' => $privateId
            );
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            #pure_dump($result);
            $userId = (int)$result[0]["roomId"]; // $userId = (int)$result[0]['roomId']
            if (empty($userId)) {
                return badreq();
             }*/

            global $KEY_ROOM_ID,$KEY_CREATOR,$KEY_NAME,$KEY_DESCRIPTION;
            $roomId = $json[$KEY_ROOM_ID];
            $creator = $json[$KEY_CREATOR];
            $name = $json[$KEY_NAME];
            $description = $json[$KEY_DESCRIPTION];

            $sql = "INSERT INTO pacco.survey
                    (roomId, creator, name, description)
                    VALUES 
                    (:roomId, :creator, :name, :description)";
            $params = array (
                ':roomId' => $roomId,
                ':creator' => $creator,
                ':name' => $name,
                ':description' => $description
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // survey question
            $sql = "SELECT last_insert_id()"; 
            // SELECT surveyId FROM pacco.survey
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            pure_dump($result);
            $surveyId = intval($result[0][0]);

            
            global $KEY_QUESTIONS, $KEY_Q_TEXT, $KEY_TYPE;
            // if (empty($questions))...
            $questions = $json[$KEY_QUESTIONS];
            foreach($questions as $key => $val) {
                $qText = $val[$KEY_Q_TEXT];
                $type = $val[$KEY_TYPE];
                $sql = "INSERT INTO survey_question
                        (surveyId, qText, type)
                        VALUES (:surveyId, :qText, :type)";
                $stmt = $pdo->prepare($sql);
                $que = array (
                    ':surveyId' => $surveyId,
                    ':qText' => $qText, 
                    ':type' => $type
                );
                $stmt->execute($que);
                /*if ($stmt->rowCount() == 0) {
                echo bbb;
                echo badreq();
                die();
            }*/
                // survey item
                $sql = "SELECT last_insert_id()"; // SELECT qId FROM pacco.survey_question
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll();
                $qId = intval($result[0][0]);

                global $KEY_ITEMS, $KEY_TEXT;
                $items = $val['items'];
                foreach($items as $key => $item){
                    $text = $item[$KEY_TEXT];
                    $sql = "INSERT INTO pacco.survey_item
                    (qId, text)
                    VALUES
                    (:qId, :text)";
                    $stmt = $pdo->prepare($sql);
                    $ite = array (
                        ':qId' => $qId,
                        ':text' => $text
                    );
                    $stmt->execute($ite);
                }
            }
            $pdo->commit();
            $pdo = null;
            return ok();
        } catch (Exception $e) {
           $pdo->rollBack();
           $pdo= null;
           return servererr();
        }
    } catch (Exception $ex) {
        $pdo = null;
        return servererr();
    }
}

function answer_survey($json) {
/*
    if(empty($surveyId) or empty($answerer)){
        return badreq();
    }
*/
    global $DNS, $USER, $PW;

    try {
        $pdo = new PDO($DNS, $USER, $PW);
           if($pdo == null){
               return servererr();
            }

        $pdo->beginTransaction();
        try {
            /*
            // roomId, privateIdの整合性
            $sql = "SELECT COUNT(*) AS num FROM room a, affiliation b, user c
            WHERE a.roomId = b.roomId
            AND b.userId = c.userId
            AND a.roomId = :roomId
            AND c.privateId = :privateId";
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            $num = (int)$result[0][0];
            if($num != 1){
                echo a;
                echo badreq();
                die();
            }*/

            global $KEY_SURVEY_ID, $KEY_ANSWERER;
            $surveyId = $json[$KEY_SURVEY_ID];
            $answerer = $json[$KEY_ANSWERER];
            $sql = "INSERT INTO answer_list
            (surveyId, answerer)
            VALUES
            (:surveyId, :answerer)";
            $list = array (
                ':surveyId' => $surveyId,
                ':answerer' => $answerer
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($list);

            // answer
            $sql = "SELECT last_insert_id()"; // answerlist
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $answerListId = intval($result[0][0]);
            
            global $KEY_ANSWERS, $KEY_Q_ID, $KEY_ANSWER_LIST_ID, $KEY_ANSWER;
            $answers = $json['answers'];
            foreach($answers as $key => $ans) {
               $answer = $ans[$KEY_ANSWER];
               $qId = $ans[$KEY_Q_ID];

               $sql = "INSERT INTO answer 
                      (answerListId, qId, answer)
                      VALUES 
                      (:answerListId, :qId, :answer)";
               $stmt = $pdo->prepare($sql);
               $an = array (
                   ':answerListId' => $answerListId,
                   ':qId' => $qId,
                   ':answer' => $answer
               );
               $stmt->execute($an);
            }
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


function get_survey_list($privateId, $roomId){
    if (empty($roomId)){
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
        a.surveyId, a.name, a.creator, a.description,a.answerWanted
        FROM survey a, user b, room c
        WHERE a.creator = b.userId
        AND a.roomId = c.roomId
        AND a.roomId = :roomId
        AND b.privateId = :privateId
        AND a.answerWanted = true";
/*
        $sql = "SELECT 
        a.surveyId, a.name, a.creator, a.description, a.answerWanted
        FROM survey a, affiliation b, user c
        WHERE a.creator = b.userId
        AND b.userId = c.userId
        AND c.privateId = :privateId
        AND a.answerWanted = true";
*/
        //SELECT
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':roomId' => $roomId, 
            ':privateId' => $privateId
        );

        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_SURVEYS, $KEY_SURVEY_ID, $KEY_NAME, $KEY_CREATOR, $KEY_DESCRIPTION, $KEY_ANSWER_WANTED;
        $surveys = array();

        foreach($result as $val){
            $sur = array (
                $KEY_SURVEY_ID => (int)$val[$KEY_SURVEY_ID],
                $KEY_NAME => $val[$KEY_NAME],
                $KEY_CREATOR => $val[$KEY_CREATOR],
                $KEY_DESCRIPTION => $val[$KEY_DESCRIPTION],
                $KEY_ANSWER_WANTED => (int)$val[$KEY_ANSWER_WANTED]
            );
            $surveys[] = $sur;
        }

        return json_encode(
            array (
                $KEY_SURVEYS => $surveys
            )
        );

     } catch (Exception $e) {
        $pdo = null;
        return servererr();
    }
}


function get_survey($privateId, $roomId, $surveyId) {

    if (empty($surveyId) == true){
        return badreq();
    }

    global $DNS, $USER, $PW;

    try{
        $pdo = new PDO($DNS, $USER, $PW);
        if($pdo == null){
            return servererr();
        }

        $sql = "SELECT
        a.qId, a.qText, b.itemId, b.text
        FROM survey_question a, survey_item b
        WHERE a.qId = b.qId
        AND a.surveyId = :surveyId
        ";
        
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':surveyId' => $surveyId
        );

        //$stmt->execute($params);
        // items
        /*$sql = "SELECT last_insert_id()";
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
            
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':roomId' => $roomId,
                ':privateId' => $privateId
            );
        }
*/
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_SURVEY_ID, $KEY_ROOM_ID, $KEY_Q_ID, $KEY_Q_TEXT, $KEY_ITEM_ID, $KEY_TEXT, $KEY_SURVEYS,$KEY_ITEMS;

        $surveys = array ();
        $qId = -1;
        $first = true;
        $items = array ();
        foreach($result as $val) {
            $queId = (int)$val['qId'];
            if($qId != $queId) { 
                // itemの挿入
                if($first) $first = false;
                else {
                    $surveyQuestionObject['items'] = $items;
                    $surveys[] = $surveyQuestionObject;
                    $items = array ();
                }
                $surveyQuestionObject = array (
                    'qId' => $queId,
                    'qText' => $val['qText']
                );
            }
             $items[] = array (
                'itemId' => (int)$val['itemId'],
                'text' => $val['text']
            );
            $qId = $queId;
        }
        $surveyQuestionObject['items'] = $items;
        $surveys[] = $surveyQuestionObject;
    
        return json_encode(
            array (
                'surveys' => $surveys
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
    if (empty($surveyId) == true){
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
        /*
        $sql = "SELECT
        a.surveyId, a.answerer, b.qId, b.answer
        FROM answer_list a, answer b, affiliation c, user d, room e, survey_question f, survey g
        WHERE a.surveyId = g.surveyId 
        AND a.answerer = d.userId
        AND a.answerListId = b.answerListId
        AND b.qId = f.qId
        AND c.userId = d.userId
        AND c.roomId = e.roomId
        AND d.privateId = :privateId
        AND e.roomId = :roomId";*/
        $sql = "SELECT * FROM answer_list a, answer b
            WHERE a.answerListId = b.answerListId
            AND a.surveyId = :surveyId";

        $stmt = $pdo->prepare($sql);
        /*$params = array (
            ':privateId' => $privateId,
            ':roomId' => $roomId
        );*/
        $params = array (
            ':surveyId' => $surveyId
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;

        global $KEY_SURVEY_ID, $KEY_ROOM_ID, $KEY_ANSWER_ID, $KEY_ANSWERER, $KEY_ROOM_ID, $KEY_Q_ID, $KEY_ANSWER, $KEY_ANSWER_LIST, $KEY_ANSWERS;

        $answerlists = array ();
        $answerListId = -1;
        $answers = array ();
        $first = true;
        foreach($result as $val){
            $ansLId = (int)$val['answerListId'];
            if ($answerListId != $ansLId) { // answer_listの変わり目
                // answersの挿入
                if ($first) $first = false;
                else {
                    $answerListObject['answers'] = $answers;
                    $answerLists[] = $answerListObject;
                    $answers = array ();
                }
                // new answer_list
                $answerListObject = array (
                    'answerListId' => $ansLId,
                    'answerer' => (int)$val['answerer']
                );
            }
            // each answer 
            $answers[] = array (
                'qId' => (int)$val['qId'],
                'answer' => $val['answer']
            );
            $answerListId = $ansLId;
        }
        // last answer_list
        $answerListObject['answers'] = $answers;
        $answerLists[] = $answerListObject;

        // $lastTime = date($TIME_FORMAT); 
        return json_encode(
            array (
                'answerLists' => $answerLists
            )
        );
    } catch(Exception $e){
        $pdo = null;
        echo servererr();
        die();
    }
}

?>
