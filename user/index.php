<?php

require "../header.php";
require "../utility.php";

// JSON KEY : create account
$KEY_NAME = "name";
$KEY_TERMINAL_ID = "terminalId";
$KEY_PASSWORD = "password";
$KEY_MAIL_ADDRESS = "mail_address";

// QUERY KEY : change account information
$KEY_PRIVATE_ID = "privateId";
# name : 
# password : 
# mail address : 

// RETURN JSON KEY : create account
$KEY_USER_ID = "userId";
# privateId : 


// processing
$req = $_SERVER["REQUEST_METHOD"];
if ($req == "POST") {
    // create account
    $json_string = file_get_contents('php://input'); # Content-Type:application/json
    $json = json_decode($json_string, true);
    $name = $json[$KEY_NAME];
    $terminalId = $json[$KEY_TERMINAL_ID];
    $password = $json[$KEY_PASSWORD];
    $mail_address = $json[$KEY_MAIL_ADDRESS];
    echo create_account($name, $terminalId, $password, $mail_address);
    return;

} else if ($req == "PUT") {
    // change account information
    $params = array ();
    parse_str(file_get_contents('php://input'), $params);
    $privateId = $params[$KEY_PRIVATE_ID];
    $name = $params[$KEY_NAME];
    $password = $params[$KEY_PASSWORD];
    $mail_address = $params[$KEY_MAIL_ADDRESS];
    echo change_account($privateId, $name, $password, $mail_address);
    return;

} else {
    echo badreq(); // Error
}

// functions
// create account
function create_account($name, $terminalId, $password, $mail_address) {
    // (terminalId : android) or (password and mail_address : web)
    if ((!empty($terminalId) or (!empty($password) and !empty($mail_address))) == false) 
        return badreq();

    global $DNS, $USER, $PW; // use global parameters

    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }
        // create private ID.
        $privateId = md5(uniqid(rand(), true)); // 32 characters
        //return sha1(uniqid(rand(), true)); // 40 characters
        //return md5(uniqid(rand(), true)) + md5(uniqid(rand(), true)); // 64 characters
        # 現在 privateIdはchar(64)となっているが、rand + rand はあまりよろしくない？(若干正規分布に近く) 

        // Insert new account.
        $pdo->beginTransaction();
        try {
            if (empty($name)) $name = 'NONAME'; // $nameが空なら'NONAME'をセット 
            # (TABLEの設定でデフォルト値を'NONAME'にしているので本当は$nameが空の場合は何も設定せずにデフォルト値が入るようにしたいが、コードが冗長になりそうなのでとりあえず放置) / (ちなみに、冗長な書き方として、SQL, paramsをemptyのif分で２回書くやり方を考えている)
            // SQL
            $sql = "INSERT INTO pacco.user
                    (privateId, name, terminalId, password, mailAddress)
                    VALUES
                    (:privateId, :name, :terminalId, :password, :mailAddress)";
            // INSERT
            $stmt = $pdo->prepare($sql);
            $params = array (
                ':privateId' => $privateId,
                ':name' => $name,
                'terminalId' => $terminalId,
                ':password' => $password,
                ':mailAddress' => $mail_address
            );
            $stmt->execute($params);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo servererr();
            //echo  $e->getMessage();
            die();
        }

        // get userId generated column
        $sql = "SELECT last_insert_id()";
        //$sql = "SELECT userId FROM pacco.user";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $userId = intval($result[0][0]);
        $pdo = null;
        
        // return usrId, privateId
        global $KEY_USER_ID, $KEY_PRIVATE_ID;
        return json_encode (
            array (
                $KEY_USER_ID => $userId,
                $KEY_PRIVATE_ID => $privateId
            )
        );

    } catch (Exception $ex) {
        $pdo = null;
        echo servererr();
        //echo $ex->getMessage();
        die();
    }
}

// change account information
function change_account($privateId, $name, $password, $mail_address) {
    // need private ID
    if (empty($privateId)) {
        echo badreq();
        die();
    }
    // need name or password or mail address
    if (empty($name) and empty($password) and empty($mail_address)) {
        echo badreq();
        die();
    }
    
    global $DNS, $USER, $PW; // use global parameter
    
    try {
        $pdo = new PDO($DNS, $USER, $PW); // connect
        if ($pdo == null) {
            echo servererr();
            die();
        }

        // Update account information
        $pdo->beginTransaction();
        try {
            $sql = "UPDATE user SET ";
            $params = array ();
            $first = true;
            if (!empty($name)) {
                $sql = $sql."name=:name";
                $first = false;
                $params[':name'] = $name;
            }
            if (!empty($password)) {
                if (!$first) $sql = $sql.", ";
                $sql = $sql."password=:password";
                $first = false;
                $params[':password'] = $password;
            }
            if (!empty($mail_address)) {
                if (!$first) $sql = $sql.", ";
                $sql = $sql."mailAddress=:mailAddress";
                $first = false;
                $params[':mailAddress'] = $mail_address;
            }
            $sql = $sql." WHERE privateId = :privateId";
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

        } catch (Exception $e) {
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