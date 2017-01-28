# server test input

## user
### create acc
    "{ \"name\" : \"user name 1\", \"terminalId\" : \"1234567890a\" }" // correct
    "{ \"password\" : \"pass\", \"mail_address\" : \"test_input@example.com\" }" // correct
    "{ \"name\" : \"user name 2\", \"mail_address\" ; \"test_input2@example.com\" }" // incorrect

### change acc
* 保留

***

## room
### create room（affiliationも確認する）
    "{ \"request\" : \"CREATE\", \"name\" : \"room name 1\", \"host\" : 1, \"purpose\" : \"purpose 1\"  }" // correct
    "{ \"request\" : \"CREATE\", \"host\" : 2 }" // correct
    "{ \"name\" : \"room name\", \"host\" : 1 }" // incorrect

### affiliation
    // 前準備, mysqlで実行
    insert into pacco.user (privateId) values ("aaaa");
    insert into pacco.user (privateId) values ("bbbb");
    // test input
    "{ \"request\" : \"AFFILIATE\", \"roomId\" : 1, \"privateId\" : \"aaaa\" }" // correct
    "{ \"request\" : \"AFFILIATE\", \"roomId\" : 1 }" // incorrect
    "{ \"roomId\" : 1, \"privateId\" : \"aaaa\" }" // incorrect

### get member
* 後で作成すする

### get room
* 後で作成すする

### get all room (searchの一時的な代用)
* 後で作成すする

***

## survey
### create survey
    "{ \"request\" : \"CREATE\", \"roomId\" : 1, \"name\" : \"survey name 1\", \"creator\" : 1, \"description\" : \"description 1\" }" // correct
    "{ \"request\" : \"CREATE\", \"roomId\" : 1, \"name\" : \"survey name 1\", \"creator\" : 1, \"description\" : \"description 1\", \"questions\" : [ { \"qText\" : \"qText 1\", \"type\" : 1, \"items\" : [ { \"text\" : \"text 1\" }, { \"text\" : \"text 2\" } ] }, { \"qText\" : \"qText 2\", \"type\" : 2, \"items\" : [ { \"text\" : \"text 3\" }, { \"text\" : \"text 4\" } ] } ] }" // correct

### answer
    "{ \"request\" : \"ANSWER\", \"surveyId\" : 1, \"answerer\" : 1 }" // correnct
    "{ \"request\" : \"ANSWER\", \"surveyId\" : 2, \"answerer\" : 2, \"answers\" : [ { \"qId\" : 1, \"answer\" : \"answer 1\" }, { \"qId\" : 2, \"answer\" : \"answer 2\" } ] }"
    "{ \"request\" : \"ANSWER\", \"surveyId\" : 2, \"answerer\" : 3, \"answers\" : [ { \"qId\" : 1, \"answer\" : \"answer 3\" }, { \"qId\" : 2, \"answer\" : \"answer 4\" } ] }" // corrent

### get survey list
    request=SURVEY_LIST&roomId=1

### get survey
    request=SURVEY&surveyId=2

### get answer
    request=ANSWER&surveyId=2

***

## chat
### get
    "{\"privateId\" : \"aaaa\", \"roomId\" : 1, \"content\" : \"content 1\" }" //correct
    "{\"privateId\" : \"bbbb\", \"roomId\" : 1, \"content\" : \"content 2\" }" //correct

### send
    roomId=1

***

## message
### send
    "{\"roomId\" : 1, \"privateId\" : \"bbbb\", \"userId\" : 1, \"content\" : \"content 1\" }" // corrent

### get
    privateId=bbbb&roomId=1









