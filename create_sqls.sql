# DB作成Ω
create database pacco;

# TABLE作成
## user
create table pacco.user (
    userId int not null auto_increment primary key,
    privateId char(32) not null unique,
    name varchar(32) not null default 'NONAME' ,
    terminalId char(64), # <-32??
    password varchar(128),
    mailAddress varchar(256)
) engine = InnoDB;

## room
create table pacco.room (
    roomId int not null auto_increment primary key,
    name varchar(128) not null default 'ROOM',
    purpose varchar(256) default '',
    host int,
    isPublic boolean not null default false,
    canJoin boolean not null default false,
    chatIsLocked boolean not null default false,
    messageIsLocked boolean not null default false,
    rtmssWanted boolean not null default false,
    isDeleted boolean not null default false,
    foreign key (host) references user(userId)
) engine = InnoDB;

## affiliation
create table pacco.affiliation (
    affiliationId int not null auto_increment primary key,
    userId int not null,
    roomId int not null,
    hasPermissionDoc boolean not null default false,
    hasPermissionSur boolean not null default false,
    affiliationTime timestamp default current_timestamp on update current_timestamp,
    foreign key (userId) references user(userId),
    foreign key (roomId) references room(roomId)
) engine = InnoDB;

## document
create table pacco.document (
    docId int not null auto_increment primary key,
    name varchar(256),
    roomId int not null,
    uploader int,
    uploadTime timestamp default current_timestamp on update current_timestamp,
    foreign key (roomId) references room(roomId),
    foreign key (uploader) references user(userId)
) engine = InnoDB;

## document memo
create table pacco.document_memo (
    memoId int not null auto_increment primary key,
    docId int not null,
    page int not null default 0,
    x int not null default 0,
    y int not null default 0,
    comment varchar(64) default '',
    creator int,
    memoTime timestamp default current_timestamp on update current_timestamp,
    foreign key (docId) references document(docId),
    foreign key (creator) references user(userId)
) engine = InnoDB;

## survey
create table pacco.survey (
    surveyId int not null auto_increment primary key,
    roomId int not null,
    creator int,
    name varchar(32) not null default 'SURVEY',
    description varchar(256) default '',
    answerWanted boolean not null default false,
    surveyTime timestamp default current_timestamp on update current_timestamp,
    foreign key (roomId) references room(roomId), 
    foreign key (creator) references user(userId)
) engine = InnoDB;

## survey type
create table pacco.survey_type (
    typeId int not null primary key,
    type varchar(16) not null
);

# survey type insert
insert into pacco.survey_type (typeId, type) values (0, "EDIT");
insert into pacco.survey_type (typeId, type) values (1, "RADIO");
insert into pacco.survey_type (typeId, type) values (2, "CHECK");

## survey question
create table pacco.survey_question (
    qId int not null auto_increment primary key,
    surveyId int not null,
    qText varchar(256) default '',
    type int not null,
    foreign key (surveyId) references survey(surveyId),
    foreign key (type) references survey_type(typeId)
) engine = InnoDB;

## survey item
create table pacco.survey_item (
    itemId int not null auto_increment primary key,
    qId int not null,
    text varchar(32),
    foreign key (qId) references survey_question(qId)
) engine = InnoDB;

## answer list
create table pacco.answer_list (
    answerListId int not null auto_increment primary key,
    surveyId int not null,
    answerer int not null,
    foreign key (surveyId) references survey(surveyId),
    foreign key (answerer) references user(userId)
) engine = InnoDB;

## answer
create table pacco.answer (
    answerId int not null auto_increment primary key,
    qId int not null,
    answerListId int not null,
    answer varchar(256) default '',
    foreign key (qId) references survey_question(qId),
    foreign key (answerListId) references answer_list(answerListId)
) engine = InnoDB;

## chat
create table pacco.chat (
    chatId int not null auto_increment primary key,
    roomId int not null,
    userId int not null,
    content varchar(128) default '',
    chatTime timestamp not null default current_timestamp on update current_timestamp,
    foreign key (roomId) references room(roomId),
    foreign key (userId) references user(userId)
) engine = InnoDB;

## message
create table pacco.message (
    messageId int not null auto_increment primary key,
    roomId int not null,
    fromUser int not null,
    toUser int not null,
    content varchar(256) default '',
    messageTime timestamp not null default current_timestamp on update current_timestamp,
    foreign key (roomId) references room(roomId),
    foreign key (fromUser) references user(userId),
    foreign key (toUser) references user(userId)
) engine = InnoDB;

## rtmss list
create table pacco.rtmss_list (
    rtmssListId int not null auto_increment primary key,
    roomId int not null,
    color1Text varchar(32) default 'COLOR1',
    color2Text varchar(32) default 'COLOR2',
    color3Text varchar(32) default 'COLOR3',
    startTime timestamp null default null,
    endTime timestamp null default null,
    foreign key (roomId) references room(roomId)
) engine = InnoDB;

## rtmss
create table pacco.rtmss (
    rtmssId int not null auto_increment primary key,
    rtmssListId int not null,
    color1Sum int not null default 0,
    color2Sum int not null default 0,
    color3Sum int not null default 0,
    startTime timestamp null default null,
    endtime timestamp null default null,
    foreign key (rtmssListId) references rtmss_list(rtmssListId)
) engine = InnoDB;