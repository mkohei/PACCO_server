<?php

require "../header.php";
require "../utility.php";

// processing
$req = $_SERVER["REQUEST_METHOD"];
if ($req == "GET") {
    // get documents
    $roomId = (int)$_GET['roomId'];
    echo get_doc($roomId);
    return;

} else {
    echo badreq();
    die();
}

// functions
function get_doc($roomId) {
    if (empty($roomId)) return badreq();
    global $DNS, $USER, $PW;
    try {
        $pdo = new PDO($DNS, $USER, $PW);
        if ($pdo == null) return servererr();

        $sql = "SELECT
            docId, name FROM document
            WHERE roomId = :roomId";
        $stmt = $pdo->prepare($sql);
        $params = array (
            ':roomId' => $roomId
        );
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $pdo = null;
        $documents = array ();
        foreach ($result as $val) {
            $d = array (
                'docId' => (int)$val['docId'],
                'name' => $val['name'],
            );
            $documents[] = $d;
        }
        return json_encode (
            array (
                'documents' => $documents
            )
        );

    } catch (Exception $e) {
        $pdo = null;
        echo servererr();
        die();
    }
}

?>