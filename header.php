<?php
//Timezone
date_default_timezone_set('Asia/Tokyo');

// DB 接続パラメータ
$dsn = "mysql:dbname=krlab_nfc;host=localhost;charset=utf8";
$user = "root";
$pass = "";

//応答処理
function ok() {
    http_response_code( 200 ) ;
    return json_encode (
        array (
            "status"=>"200 OK"
        )
    );
}
function badreq() {
    http_response_code( 400 ) ;
    return json_encode (
        array (
            "status"=>"400 Bad_Request"
        )
    );

}
function servererr() {
    http_response_code( 500 ) ;
    return json_encode (
        array (
            "status"=>"500 Internal_Server_Error"
        )
    );
}

?>
