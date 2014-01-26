<?php
// ===========================================================================================
//
// SQLCreateUserAndGroupTables.php
//
// SQL statements to create the tables for the User and group tables.
//
// WARNING: Do not forget to check input variables for SQL injections.
//
// Author: Mats Ljungquist
//

$imageLink = WS_IMAGES;

// Get the tablenames
$tSida           = DBT_Sida;
$tMatch          = DBT_Match;
$tTournament     = DBT_Tournament;
$tUserTournament = DBT_UserTournament;
$tPointFilter    = DBT_PointFilter;
$tUser 		 = DBT_User;
$tGroup 	 = DBT_Group;
$tGroupMember 	 = DBT_GroupMember;
$tStatistics 	 = DBT_Statistics;

// Get the SP/UDF/trigger names
$spAuthenticateUser            = DBSP_AuthenticateUser;
$spCreateUser                  = DBSP_CreateUser;
$trInsertUser	               = DBTR_TInsertUser;
$spGetUserDetails              = DBSP_GetUserDetails;
$spSetUserDetails              = DBSP_SetUserDetails;
$spSetUserPassword             = DBSP_SetUserPassword;
$spSetUserEmail                = DBSP_SetUserEmail;
$spSetUserArmy                 = DBSP_SetUserArmy;
$spUpdateLastLogin             = DBSP_UpdateLastLogin;
$spSetUserAvatar               = DBSP_SetUserAvatar;
$spSetUserGravatar             = DBSP_SetUserGravatar;
$spSetUserNameAndEmail         = DBSP_SetUserNameAndEmail;
$spSetTournamentUser           = DBSP_SetTournamentUser;
$spCreateUserAccountOrEmail    = DBSP_CreateUserAccountOrEmail;
$spCreateUserAccountTournament = DBSP_CreateUserAccountTournament;
$spDeleteUser                  = DBSP_DeleteUser;

// Match related sps
$spCreateMatch                  = DBSP_CreateMatch;
$spUpdateMatchScore             = DBSP_UpdateMatchScore;
$spDeleteMatch                  = DBSP_DeleteMatch;
$spDeleteAllMatchesOnRound      = DBSP_DeleteAllMatchesOnRound;

// Tournament related sps
$spCreateTournament             = DBSP_CreateTournament;
$spEditTournament               = DBSP_EditTournament;
$spEditSelectedValuesTournament = DBSP_EditSelectedValuesTournament;
$spChangeActiveTournament       = DBSP_ChangeActiveTournament;
$spSetJsonScoreProxyTournament  = DBSP_SetJsonScoreProxyTournament;
$spDeleteTournament             = DBSP_DeleteTournament;

// User - Tournament related sps
$spLeaveTournament              = DBSP_LeaveTournament;
$spJoinTournament               = DBSP_JoinTournament;

// Point filter related sps
$spCreatePointFilter            = DBSP_CreatePointFilter;
$spEditPointFilter              = DBSP_EditPointFilter;

$fCheckUserIsAdmin              = DBUDF_CheckUserIsAdmin;

$fGetGravatarLinkFromEmail      = DBUDF_GetGravatarLinkFromEmail;

// Create the query
$query = <<<EOD
DROP TABLE IF EXISTS {$tStatistics};
DROP TABLE IF EXISTS {$tSida};

DROP TABLE IF EXISTS {$tMatch};
DROP TABLE IF EXISTS {$tPointFilter};
DROP TABLE IF EXISTS {$tUserTournament};
DROP TABLE IF EXISTS {$tTournament};
DROP TABLE IF EXISTS {$tGroupMember};
DROP TABLE IF EXISTS {$tUser};
DROP TABLE IF EXISTS {$tGroup};

--
-- Table for the User
--
CREATE TABLE {$tUser} (

  -- Primary key(s)
  idUser INT AUTO_INCREMENT NOT NULL PRIMARY KEY,

  -- Attributes
  accountUser CHAR(20) NULL UNIQUE,
  nameUser CHAR(100),
  emailUser CHAR(100) NULL UNIQUE,
  lastLoginUser DATETIME NOT NULL,
  passwordUser CHAR(32) NOT NULL,
  avatarUser VARCHAR(256),
  gravatarUser VARCHAR(100) NULL,
  deletedUser BOOL NOT NULL,
  armyUser CHAR(100) NULL,
  activeUser BOOL NOT NULL
  
);


--
-- Table for the Group
--
CREATE TABLE {$tGroup} (

  -- Primary key(s)
  idGroup CHAR(3) NOT NULL PRIMARY KEY,

  -- Attributes
  nameGroup CHAR(40) NOT NULL
);


--
-- Table for the GroupMember
--
CREATE TABLE {$tGroupMember} (

  -- Primary key(s)
  --
  -- The PK is the combination of the two foreign keys, see below.
  --

  -- Foreign keys
  GroupMember_idUser INT NOT NULL,
  GroupMember_idGroup CHAR(3) NOT NULL,

  FOREIGN KEY (GroupMember_idUser) REFERENCES {$tUser}(idUser),
  FOREIGN KEY (GroupMember_idGroup) REFERENCES {$tGroup}(idGroup),

  PRIMARY KEY (GroupMember_idUser, GroupMember_idGroup)

  -- Attributes

);

--
-- Table for the Tournament
--
CREATE TABLE {$tTournament} (

  -- Primary key(s)
  idTournament INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
  
  -- Foreign keys
  creatorTournament_idUser INT NOT NULL,

  FOREIGN KEY (creatorTournament_idUser) REFERENCES {$tUser}(idUser),
      
  -- Attributes
  placeTournament VARCHAR(200) NULL,
  roundsTournament INT NOT NULL,
  typeTournament VARCHAR(30) NOT NULL,
  activeTournament BOOL NOT NULL,
  byeScoreTournament INT NOT NULL,
  createdTournament DATETIME NOT NULL,
  dateFromTournament DATETIME NOT NULL,
  dateTomTournament DATETIME NOT NULL,
  tieBreakersTournament VARCHAR(200) NULL,
  useProxyTournament BOOL NOT NULL,
  jsonScoreProxyTournament TEXT NULL
);

--
-- Table for the user-tournament manyToMany-relation
--
CREATE TABLE {$tUserTournament} (

  -- Primary key(s)
  --
  -- The PK is the combination of the two foreign keys, see below.
  --

  -- Foreign keys
  UserTournament_idUser INT NOT NULL,
  UserTournament_idTournament INT NOT NULL,

  FOREIGN KEY (UserTournament_idUser) REFERENCES {$tUser}(idUser),
  FOREIGN KEY (UserTournament_idTournament) REFERENCES {$tTournament}(idTournament),

  PRIMARY KEY (UserTournament_idUser, UserTournament_idTournament),

  -- Attributes
  joinDateUserTournament DATETIME NOT NULL
);

--
-- Table for a Match (a round between two players/users)
--
CREATE TABLE {$tMatch} (

  -- Primary key(s)
  idMatch INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
  
  -- Foreign keys
  playerOneMatch_idUser INT NOT NULL,
  playerTwoMatch_idUser INT NULL,
  tRefMatch_idTournament INT NOT NULL,

  FOREIGN KEY (playerOneMatch_idUser) REFERENCES {$tUser}(idUser),
  FOREIGN KEY (playerTwoMatch_idUser) REFERENCES {$tUser}(idUser),
  FOREIGN KEY (tRefMatch_idTournament) REFERENCES {$tTournament}(idTournament),
      
  -- Attributes
  playerOneScoreMatch INT NOT NULL,
  playerTwoScoreMatch INT NOT NULL,
  roundMatch INT NOT NULL,
  lastUpdateMatch DATETIME NOT NULL
  
);

--
-- Table for a Match (a round between two players/users)
--
CREATE TABLE {$tPointFilter} (

  -- Primary key(s)
  idPointFilter INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
  
  -- Foreign keys
  tRefPointFilter_idTournament INT NOT NULL,

  FOREIGN KEY (tRefPointFilter_idTournament) REFERENCES {$tTournament}(idTournament),
      
  -- Attributes
  
  lowScorePointFilter INT NOT NULL,
  highScorePointFilter INT NOT NULL,
  playerOneScorePointFilter INT NOT NULL,
  playerTwoScorePointFilter INT NOT NULL
  
);

--
-- Table for the Statistics
--
DROP TABLE IF EXISTS {$tStatistics};
CREATE TABLE {$tStatistics} (

  -- Primary key(s)
  -- Foreign keys
  Statistics_idUser INT NOT NULL,

  FOREIGN KEY (Statistics_idUser) REFERENCES {$tUser}(idUser),
  PRIMARY KEY (Statistics_idUser),

  -- Attributes
  numOfArticlesStatistics INT NOT NULL DEFAULT 0
);

--
-- SP to create a new user
--
DROP PROCEDURE IF EXISTS {$spCreateUser};
CREATE PROCEDURE {$spCreateUser}
(
	IN anAccountUser CHAR(20),
	IN aPassword CHAR(32)
)
BEGIN
        INSERT INTO {$tUser}
                (accountUser, passwordUser, lastLoginUser, deletedUser)
                VALUES
                (anAccountUser, md5(aPassword), NOW(), FALSE);
        INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES (LAST_INSERT_ID(), 'usr');
        CALL {$spAuthenticateUser}(anAccountUser,aPassword);
END;
   
--
-- SP to create a new Tournament
--
DROP PROCEDURE IF EXISTS {$spCreateTournament};
CREATE PROCEDURE {$spCreateTournament}
(
	IN anIdCreator INT,
    IN aPlace VARCHAR(200),
    IN aNrOfRounds INT,
    IN aType VARCHAR(30),
    IN anActiveTournament BOOL,
    IN aByeScore INT,
    IN aDateFrom DATETIME,
    IN aDateTom DATETIME,
    IN aTieBreakers VARCHAR(200),
    IN anUseProxy BOOL,
    IN aJsonScoreProxy TEXT,
    OUT aTournamentId INT
)
BEGIN
	INSERT INTO {$tTournament}
        (creatorTournament_idUser, placeTournament, roundsTournament, typeTournament, activeTournament, byeScoreTournament, createdTournament, dateFromTournament, dateTomTournament, tieBreakersTournament, useProxyTournament, jsonScoreProxyTournament)
    VALUES
        (anIdCreator, aPlace, aNrOfRounds, aType, anActiveTournament, aByeScore, NOW(), aDateFrom, aDateTom, aTieBreakers, anUseProxy, aJsonScoreProxy);

    SELECT LAST_INSERT_ID() INTO aTournamentId;
END;
   
--
-- SP to edit a Tournament
--
DROP PROCEDURE IF EXISTS {$spEditTournament};
CREATE PROCEDURE {$spEditTournament}
(
    IN aTournamentId INT,
    IN aPlace VARCHAR(200),
    IN aNrOfRounds INT,
    IN aType VARCHAR(30),
    IN anActiveTournament BOOL,
    IN aByeScore INT,
    IN aDateFrom DATETIME,
    IN aDateTom DATETIME,
    IN aTieBreakers VARCHAR(200),
    IN anUseProxy BOOL,
    IN aJsonScoreProxy TEXT
)
BEGIN
    -- Only update if there are no matches in the tournament
    DECLARE i INT UNSIGNED;
	
	SELECT COUNT(idMatch) INTO i FROM {$tMatch} 
	WHERE 
		tRefMatch_idTournament = aTournamentId;
    IF i = 0 THEN
    BEGIN
        UPDATE {$tTournament} SET
                placeTournament          = aPlace,
                roundsTournament         = aNrOfRounds,
                typeTournament           = aType,
                activeTournament         = anActiveTournament,
                byeScoreTournament       = aByeScore,
                dateFromTournament       = aDateFrom,
                dateTomTournament        = aDateTom,
                tieBreakersTournament    = aTieBreakers,
                useProxyTournament       = anUseProxy,
                jsonScoreProxyTournament = aJsonScoreProxy
        WHERE
                idTournament = aTournamentId
        LIMIT 1;
    END;
    END IF;
END;
   
--
-- SP to delete a Tournament
--
DROP PROCEDURE IF EXISTS {$spDeleteTournament};
CREATE PROCEDURE {$spDeleteTournament}
(
    IN aTournamentId INT
)
BEGIN
    -- First delete point filter on tournament
    DELETE FROM {$tPointFilter}
    WHERE
        tRefPointFilter_idTournament = aTournamentId;
   
    -- Then delete matches
    DELETE FROM {$tMatch}
    WHERE
        tRefMatch_idTournament = aTournamentId;
   
    -- Then delete all matching rows in the user-tournament-relation
    DELETE FROM {$tUserTournament}
    WHERE
        UserTournament_idTournament = aTournamentId;
    
    -- And finally, delete the tournament
    DELETE FROM {$tTournament}
    WHERE
        idTournament = aTournamentId
    LIMIT 1;
END;
   
--
-- SP to edit a Tournament
--
DROP PROCEDURE IF EXISTS {$spEditSelectedValuesTournament};
CREATE PROCEDURE {$spEditSelectedValuesTournament}
(
    IN aTournamentId INT,
    IN aPlace VARCHAR(200),
    IN aNrOfRounds INT,
    IN aByeScore INT,
    IN aDateFrom DATETIME,
    IN aDateTom DATETIME,
    IN aTieBreakers VARCHAR(200),
    IN anUseProxy BOOL,
    IN anActive BOOL
)
BEGIN
    UPDATE {$tTournament} SET
            roundsTournament   = aNrOfRounds,
            placeTournament    = aPlace,
            byeScoreTournament = aByeScore,
            dateFromTournament = aDateFrom,
            dateTomTournament  = aDateTom,
            tieBreakersTournament = aTieBreakers,
            useProxyTournament = anUseProxy,
            activeTournament = anActive
    WHERE
            idTournament = aTournamentId
    LIMIT 1;
END;
   
--
-- SP to store a json representation of the score filter matrix in the database.
-- The score filter matrix can be used to translate a score interval into a set
-- score ratio.
--
DROP PROCEDURE IF EXISTS {$spSetJsonScoreProxyTournament};
CREATE PROCEDURE {$spSetJsonScoreProxyTournament}
(
    IN aTournamentId INT,
    IN aJsonScoreProxy TEXT
)
BEGIN
    UPDATE {$tTournament} SET
            jsonScoreProxyTournament = aJsonScoreProxy
    WHERE
            idTournament = aTournamentId
    LIMIT 1;
END;
   
--
-- SP to activate or deactivate a tournament.
--
DROP PROCEDURE IF EXISTS {$spChangeActiveTournament};
CREATE PROCEDURE {$spChangeActiveTournament}
(
    IN aTournamentId INT,
    IN anActiveTournament BOOL
)
BEGIN
    UPDATE {$tTournament} SET
            activeTournament = anActiveTournament
    WHERE
            idTournament = aTournamentId
    LIMIT 1;
END;

--
-- SP to join a tournament
--
DROP PROCEDURE IF EXISTS {$spJoinTournament};
CREATE PROCEDURE {$spJoinTournament}
(
    IN aTournamentId INT,
    IN anUserId INT
)
BEGIN    
    DECLARE validHits INT;
    SELECT COUNT(*) INTO validHits
    FROM {$tTournament} 
    WHERE {$tTournament}.idTournament = aTournamentId AND 
        CURDATE() <= {$tTournament}.dateFromTournament;
            
    IF validHits = 1 THEN
        INSERT INTO {$tUserTournament}
            (UserTournament_idUser, UserTournament_idTournament, joinDateUserTournament)
            VALUES
            (anUserId, aTournamentId, NOW());
    END IF;
END;

--
-- SP to leave a tournament
--
DROP PROCEDURE IF EXISTS {$spLeaveTournament};
CREATE PROCEDURE {$spLeaveTournament}
(
    IN aTournamentId INT,
    IN anUserId INT
)
BEGIN    
    DELETE FROM {$tUserTournament}
    WHERE
        UserTournament_idUser = anUserId AND
        UserTournament_idTournament = aTournamentId;
END;
    
--
-- SP to create a point filter
--
DROP PROCEDURE IF EXISTS {$spCreatePointFilter};

--
-- SP to edit a Tournament
--
DROP PROCEDURE IF EXISTS {$spEditPointFilter};


--
-- SP to create a new Match
--
DROP PROCEDURE IF EXISTS {$spCreateMatch};
CREATE PROCEDURE {$spCreateMatch}
(
	IN anIdUserOne     INT,
    IN anIdUserTwo     INT,
	IN aRound          INT,
    IN aTournamentId   INT,
    IN aScorePlayerOne INT,
    IN aScorePlayerTwo INT,
    OUT aMatchId       INT
)
BEGIN
        
    IF anIdUserTwo > 0 THEN
		INSERT INTO {$tMatch}
        (playerOneMatch_idUser, playerTwoMatch_idUser, playerOneScoreMatch, playerTwoScoreMatch, roundMatch, lastUpdateMatch, tRefMatch_idTournament)
        VALUES
        (anIdUserOne, anIdUserTwo, aScorePlayerOne, aScorePlayerTwo, aRound, NOW(), aTournamentId);
    ELSE
        INSERT INTO {$tMatch}
        (playerOneMatch_idUser, playerOneScoreMatch, playerTwoScoreMatch, roundMatch, lastUpdateMatch, tRefMatch_idTournament)
        VALUES
        (anIdUserOne, aScorePlayerOne, aScorePlayerTwo, aRound, NOW(), aTournamentId);
	END IF;	
    
    SELECT LAST_INSERT_ID() INTO aMatchId;
END;
   
--
-- SP to update score on Match
--
DROP PROCEDURE IF EXISTS {$spUpdateMatchScore};
CREATE PROCEDURE {$spUpdateMatchScore}
(
    IN aIdMatch        INT,
	IN aScorePlayerOne INT,
    IN aScorePlayerTwo INT
)
BEGIN
    UPDATE {$tMatch} SET
            playerOneScoreMatch = aScorePlayerOne,
            playerTwoScoreMatch = aScorePlayerTwo,
            lastUpdateMatch = NOW()
    WHERE
            idMatch = aIdMatch
    LIMIT 1;
END;
   
--
-- SP to delete Match
--
DROP PROCEDURE IF EXISTS {$spDeleteMatch};
CREATE PROCEDURE {$spDeleteMatch}
(
    IN aIdMatch        INT
)
BEGIN
        DELETE FROM {$tMatch}
        WHERE
            idMatch = aIdMatch
        LIMIT 1;
END;
   
--
-- SP to delete Match
--
DROP PROCEDURE IF EXISTS {$spDeleteAllMatchesOnRound};
CREATE PROCEDURE {$spDeleteAllMatchesOnRound}
(
    IN aRound INT,
    IN aTournamentId INT
)
BEGIN
        DELETE FROM {$tMatch}
        WHERE
            roundMatch = aRound AND
            tRefMatch_idTournament = aTournamentId;
END;

--
-- SP to create a new user based on either account name or email
--
DROP PROCEDURE IF EXISTS {$spCreateUserAccountOrEmail};
CREATE PROCEDURE {$spCreateUserAccountOrEmail}
(
	IN anAccountUser CHAR(20),
        IN aNameUser CHAR(100),
        IN anEmailUser CHAR(100),
	IN aPassword CHAR(32)
)
BEGIN
    DECLARE authAttribute CHAR(100);
    IF anEmailUser = '' THEN
        BEGIN
            SET authAttribute = anAccountUser;
        END;
    ELSE
        BEGIN
            SET authAttribute = anEmailUser;
        END;
    END IF;
    INSERT INTO {$tUser}
            (accountUser, emailUser, nameUser, passwordUser, lastLoginUser, deletedUser)
            VALUES
            (anAccountUser, anEmailUser, aNameUser, md5(aPassword), NOW(), FALSE);
    INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES (LAST_INSERT_ID(), 'usr');
    CALL {$spAuthenticateUser}(authAttribute,aPassword);
END;
   
   --
-- SP to create a new user based on either account name or email
--
DROP PROCEDURE IF EXISTS {$spCreateUserAccountTournament};
CREATE PROCEDURE {$spCreateUserAccountTournament}
(
	IN anAccountUser CHAR(20),
    IN aNameUser CHAR(100),
    IN anArmyUser CHAR(100),
	IN aPassword CHAR(32),
    IN anActiveUser BOOL
)
BEGIN
    
    INSERT INTO {$tUser}
            (accountUser, armyUser, nameUser, passwordUser, lastLoginUser, deletedUser, activeUser)
            VALUES
            (anAccountUser, anArmyUser, aNameUser, md5(aPassword), NOW(), FALSE, anActiveUser);
    INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES (LAST_INSERT_ID(), 'usr');
    CALL {$spAuthenticateUser}(anAccountUser,aPassword);
END;

--
-- SP to authenticate a user
--
DROP PROCEDURE IF EXISTS {$spAuthenticateUser};
CREATE PROCEDURE {$spAuthenticateUser}
(
	IN anAccountUserOrEmail CHAR(100),
	IN aPassword CHAR(32)
)
BEGIN
	SELECT
	idUser AS id,
	accountUser AS account,
        nameUser AS name,
        emailUser AS email,
        avatarUser AS avatar,
	GroupMember_idGroup AS groupid
FROM {$tUser} AS U
	INNER JOIN {$tGroupMember} AS GM
		ON U.idUser = GM.GroupMember_idUser
WHERE
        (
	accountUser	= anAccountUserOrEmail AND
	passwordUser 	= md5(aPassword)
        )
        OR
        (
	emailUser	= anAccountUserOrEmail AND
	passwordUser 	= md5(aPassword)
        )
;
END;
        
--
-- SP to get user details
--
DROP PROCEDURE IF EXISTS {$spGetUserDetails};
CREATE PROCEDURE {$spGetUserDetails}
(
	IN anIdUser INT
)
BEGIN
	SELECT
	idUser AS id,
	accountUser AS account,
        nameUser AS name,
        emailUser AS email,
        avatarUser AS avatar,
        armyUser AS army,
        gravatarUser AS gravatar,
        {$fGetGravatarLinkFromEmail}(gravatarUser, 60) AS gravatarsmall,
	GroupMember_idGroup AS groupid,
        nameGroup AS groupname
FROM {$tUser} AS U
	INNER JOIN {$tGroupMember} AS GM
		ON U.idUser = GM.GroupMember_idUser
        INNER JOIN {$tGroup} AS G
                ON GM.GroupMember_idGroup = G.idGroup
WHERE
	idUser = anIdUser
;
END;
        
--
-- SP to delete user
--
DROP PROCEDURE IF EXISTS {$spDeleteUser};
CREATE PROCEDURE {$spDeleteUser}
(
        IN anIdUser INT
)
BEGIN
    DECLARE tournamentCreator INT;
    SET tournamentCreator = 0;
    -- Only delete the user if he/she is not a creator of any tournament or has any match results.
    SELECT creatorTournament_idUser INTO tournamentCreator FROM {$tTournament}
        WHERE creatorTournament_idUser = anIdUser;
    SELECT playerOneMatch_idUser INTO tournamentCreator FROM {$tMatch}
        WHERE
            playerOneMatch_idUser = anIdUser;
    SELECT playerTwoMatch_idUser INTO tournamentCreator FROM {$tMatch}
        WHERE
            playerTwoMatch_idUser = anIdUser; 

    IF tournamentCreator != anIdUser THEN
    BEGIN
        DELETE FROM {$tMatch}
        WHERE
            playerOneMatch_idUser = anIdUser OR
            playerTwoMatch_idUser = anIdUser;
        
        -- Then delete all matching rows in the user-tournament-relation
        DELETE FROM {$tUserTournament}
        WHERE
            UserTournament_idUser = anIdUser;
            
        DELETE FROM {$tStatistics}
        WHERE
            Statistics_idUser = anIdUser;
        
        DELETE FROM {$tGroupMember}
        WHERE
            GroupMember_idUser = anIdUser;
        
        DELETE FROM {$tUser}
        WHERE
            idUser = anIdUser
        LIMIT 1;
        
        SELECT * FROM {$tUser}
        WHERE
            idUser = anIdUser
        LIMIT 1;
    END;
    END IF;
END;
      
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserPassword};
CREATE PROCEDURE {$spSetUserPassword}
(
        IN anIdUser INT,
        IN aPassword CHAR(32)
)
BEGIN
        UPDATE {$tUser} SET
                passwordUser = md5(aPassword)
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
 
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserNameAndEmail};
CREATE PROCEDURE {$spSetUserNameAndEmail}
(
        IN anIdUser INT,
        IN anAccountUser CHAR(20),
        IN aNameUser CHAR(100),
        IN anEmailUser CHAR(100)
)
BEGIN
        UPDATE {$tUser} SET
                accountUser = anAccountUser,
                nameUser = aNameUser,
                emailUser = anEmailUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;      

--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetTournamentUser};
CREATE PROCEDURE {$spSetTournamentUser}
(
        IN anIdUser INT,
        IN anAccountUser CHAR(20),
        IN aNameUser CHAR(100),
        IN anArmyUser CHAR(100),
        IN anActiveUser BOOL
)
BEGIN
        UPDATE {$tUser} SET
                accountUser = anAccountUser,
                nameUser = aNameUser,
                armyUser = anArmyUser,
                activeUser = anActiveUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
        
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserEmail};
CREATE PROCEDURE {$spSetUserEmail}
(
        IN anIdUser INT,
        IN anEmailUser CHAR(100)
)
BEGIN
        UPDATE {$tUser} SET
                emailUser = anEmailUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;

--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserArmy};
CREATE PROCEDURE {$spSetUserArmy}
(
        IN anIdUser INT,
        IN anArmyUser CHAR(100)
)
BEGIN
        UPDATE {$tUser} SET
                armyUser = anArmyUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;

--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spUpdateLastLogin};
CREATE PROCEDURE {$spUpdateLastLogin}
(
        IN anIdUser INT
)
BEGIN
        UPDATE {$tUser} SET
                lastLoginUser = NOW()
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
        
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserAvatar};
CREATE PROCEDURE {$spSetUserAvatar}
(
        IN anIdUser INT,
        IN anAvatarUser VARCHAR(256)
)
BEGIN
        UPDATE {$tUser} SET
                avatarUser = anAvatarUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
        
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserGravatar};
CREATE PROCEDURE {$spSetUserGravatar}
(
        IN anIdUser INT,
        IN aGravatarUser VARCHAR(256)
)
BEGIN
        UPDATE {$tUser} SET
                gravatarUser = aGravatarUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
        
--
-- SP to set user details
--
DROP PROCEDURE IF EXISTS {$spSetUserDetails};
CREATE PROCEDURE {$spSetUserDetails}
(
        IN anIdUser INT,
        IN aNameUser CHAR(100),
        IN anEmailUser CHAR(100),
        IN anAvatarUser VARCHAR(256),
        IN aPassword CHAR(32),
        IN anArmyUser CHAR(100),
        IN anActiveUser BOOL
)
BEGIN
        UPDATE {$tUser} SET
                nameUser = aNameUser,
                emailUser = anEmailUser,
                avatarUser = anAvatarUser,
                passwordUser = md5(aPassword),
                armyUser = anArmyUser,
                activeUser = anActiveUser
        WHERE
                idUser = anIdUser
        LIMIT 1;
END;
        
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
--
--  Create UDF that checks if user is member of group adm.
--
DROP FUNCTION IF EXISTS {$fCheckUserIsAdmin};
CREATE FUNCTION {$fCheckUserIsAdmin}
(
	aUserId INT
)
RETURNS BOOLEAN
READS SQL DATA
BEGIN
	DECLARE isAdmin INT;
	
	SELECT idUser INTO isAdmin
	FROM {$tUser} AS U
		INNER JOIN {$tGroupMember} AS GM
			ON U.idUser = GM.GroupMember_idUser
		INNER JOIN {$tGroup} AS G
			ON G.idGroup = GM.GroupMember_idGroup
	WHERE
		idGroup = 'adm' AND
		idUser = aUserId;
		
	RETURN (isAdmin OR 0);		
END;
        
-- 
-- Function to create a link to gravatar.com from an emailadress.
-- http://en.gravatar.com/site/implement/url
--
DROP FUNCTION IF EXISTS {$fGetGravatarLinkFromEmail};
CREATE FUNCTION {$fGetGravatarLinkFromEmail}
(	
    aEmail CHAR(100),	
    aSize INT
)
RETURNS CHAR(255)
READS SQL DATA
BEGIN	
    DECLARE link CHAR(255);
    SELECT CONCAT('http://www.gravatar.com/avatar/', MD5(LOWER(aEmail)), '.jpg?s=', aSize)
        INTO link;
    RETURN link;
END;

--
-- Create trigger for Statistics
-- Add row when new user is created
--
DROP TRIGGER IF EXISTS {$trInsertUser};
CREATE TRIGGER {$trInsertUser}
AFTER INSERT ON {$tUser}
FOR EACH ROW
BEGIN
  INSERT INTO {$tStatistics} (Statistics_idUser) VALUES (NEW.idUser);
END;


--
-- Add default user(s)
--
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('admin', 'admin@noreply.se', 'Mr Admin', NOW(), md5('hemligt'), '{$imageLink}woman_60x60.png', '', FALSE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Hobbylim', 'mats@noreply.se', 'Mats Ljungquist', NOW(), md5('Hobbylim'), '{$imageLink}man_60x60.png', 'Vampire Counts', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('doe', 'doe@noreply.se', 'John/Jane Doe', NOW(), md5('doe'), '{$imageLink}man_60x60.png', 'Skaven', FALSE);
    

INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Akbar', 'akbar@noreply.se', 'Anders Lindblad', NOW(), md5('Akbar'), '{$imageLink}man_60x60.png', 'Empire', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Jonatan', 'jonathan@noreply.se', 'Jonatan Viklund', NOW(), md5('Jonatan'), '{$imageLink}man_60x60.png', 'Lizardmen', FALSE);
    
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Svullom', 'svullom@noreply.se', 'Alexander Larsson', NOW(), md5('Svullom'), '{$imageLink}man_60x60.png', 'Skaven', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Echunia', 'echunia@noreply.se', 'Casimir Ehrenborg', NOW(), md5('Echunia'), '{$imageLink}man_60x60.png', 'Tomb Kings', FALSE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Hugo', 'hugo@noreply.se', 'Hugo Nordland', NOW(), md5('Hugo'), '{$imageLink}man_60x60.png', 'High Elves', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Gustav', 'gustav@noreply.se', 'Gustav Weberup', NOW(), md5('Gustav'), '{$imageLink}man_60x60.png', 'High Elves', FALSE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('TiburtiusMarkus', 'tiburtiusmarkus@noreply.se', 'Bertil Persson', NOW(), md5('TiburtiusMarkus'), '{$imageLink}man_60x60.png', 'Warriors of Chaos', TRUE);
    
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Southpaw', 'southpaw@noreply.se', 'Henrik Jönsson', NOW(), md5('Southpaw'), '{$imageLink}man_60x60.png', 'High Elves', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Tor', 'tor@noreply.se', 'Tor Nilsson', NOW(), md5('Tor'), '{$imageLink}man_60x60.png', 'Wood Elves', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Marcus', 'marcus@noreply.se', 'Marcus Altin', NOW(), md5('Marcus'), '{$imageLink}man_60x60.png', 'Lizardmen', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('MR. GRUMPY', 'rasmus@noreply.se', 'Rasmus Törnqvist', NOW(), md5('Rasmus'), '{$imageLink}man_60x60.png', 'High Elves', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('Zilan', 'zilan@noreply.se', 'Carl Lohmander', NOW(), md5('Zilan'), '{$imageLink}man_60x60.png', 'Lizardmen', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('snyggejygge', 'snyggejygge@noreply.se', 'Jörgen', NOW(), md5('snyggejygge'), '{$imageLink}man_60x60.png', 'Warriors of Chaos', TRUE);
INSERT INTO {$tUser} (accountUser, emailUser, nameUser, lastLoginUser, passwordUser, avatarUser, armyUser, activeUser)
VALUES ('lilljonas', 'lilljonas@noreply.se', 'Jonas Svensson', NOW(), md5('lilljonas'), '{$imageLink}man_60x60.png', 'Empire', TRUE);

--
-- Add default groups
--
INSERT INTO {$tGroup} (idGroup, nameGroup) VALUES ('adm', 'Administrators of the site');
INSERT INTO {$tGroup} (idGroup, nameGroup) VALUES ('usr', 'Regular users of the site');


--
-- Add default groupmembers
--
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'admin'), 'adm');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'doe'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Hobbylim'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Akbar'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Jonatan'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Svullom'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
	VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Echunia'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Hugo'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Gustav'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'TiburtiusMarkus'), 'usr');
        
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Southpaw'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Tor'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Marcus'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'MR. GRUMPY'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'Zilan'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'snyggejygge'), 'usr');
INSERT INTO {$tGroupMember} (GroupMember_idUser, GroupMember_idGroup)
    VALUES ((SELECT idUser FROM {$tUser} WHERE accountUser = 'lilljonas'), 'usr');  

INSERT INTO {$tTournament} (`idTournament`, `creatorTournament_idUser`, `placeTournament`, `roundsTournament`, `typeTournament`, `activeTournament`, `byeScoreTournament`, `createdTournament`, `dateFromTournament`, `dateTomTournament`, `tieBreakersTournament`, `useProxyTournament`, `jsonScoreProxyTournament`) VALUES
(1, 1, 'DMF', 3, 'Swiss', 1, 1000, '2013-10-20 00:07:38', '2013-10-20 09:00:01', '2013-10-20 21:00:01', 'internalwinner,orgscore,mostwon', 1, null);

SET @aTournamentId = 0;
CALL {$spCreateTournament}(8, 'Here', 3, 'Swiss', true, 1000, NOW() + interval 30 day, NOW() + interval 30 day, "internalwinner", false, null, @aTournamentId);
SELECT @aTournamentId AS id;

INSERT INTO {$tMatch} (`idMatch`, `playerOneMatch_idUser`, `playerTwoMatch_idUser`, `tRefMatch_idTournament`, `playerOneScoreMatch`, `playerTwoScoreMatch`, `roundMatch`, `lastUpdateMatch`) VALUES
(2, 10, 11, 2, 2205, 605, 1, '2014-02-15 11:33:35'),
(3, 13, 12, 2, 759, 659, 1, '2014-02-15 11:33:35'),
(4, 4, 14, 2, 2125, 560, 1, '2014-02-15 11:33:35'),
(30, 8, 6, 2, 1795, 550, 1, '2013-10-20 11:33:35'),
(31, 15, 2, 2, 488, 809, 1, '2013-10-20 11:33:35');

INSERT INTO {$tUserTournament} (`UserTournament_idUser`, `UserTournament_idTournament`, `joinDateUserTournament`) VALUES
(2, 2, '2014-02-15 11:33:35'),
(4, 2, '2014-02-15 11:33:35'),
(6, 2, '2014-02-15 11:33:35'),
(8, 2, '2014-02-15 11:33:35'),
(10, 2, '2014-02-15 11:33:35'),
(11, 2, '2014-02-15 11:33:35'),
(12, 2, '2014-02-15 11:33:35'),
(13, 2, '2014-02-15 11:33:35'),
(14, 2, '2014-02-15 11:33:35'),
(15, 2, '2014-02-15 11:33:35');

INSERT INTO {$tMatch} (`idMatch`, `playerOneMatch_idUser`, `playerTwoMatch_idUser`, `tRefMatch_idTournament`, `playerOneScoreMatch`, `playerTwoScoreMatch`, `roundMatch`, `lastUpdateMatch`) VALUES
(10, 10, 11, 1, 2205, 605, 1, '2013-10-20 11:33:35'),
(9, 13, 12, 1, 759, 659, 1, '2013-10-20 11:33:35'),
(8, 4, 14, 1, 2125, 560, 1, '2013-10-20 11:33:35'),
(7, 8, 6, 1, 1795, 550, 1, '2013-10-20 11:33:35'),
(6, 15, 2, 1, 488, 809, 1, '2013-10-20 11:33:35'),
(11, 6, 14, 1, 565, 903, 2, '2013-10-20 15:48:20'),
(12, 11, 12, 1, 494, 1027, 2, '2013-10-20 15:48:20'),
(13, 15, 13, 1, 560, 1145, 2, '2013-10-20 15:48:20'),
(14, 2, 8, 1, 815, 1484, 2, '2013-10-20 15:48:20'),
(15, 4, 10, 1, 2275, 120, 2, '2013-10-20 15:48:20'),
(28, 8, 4, 1, 321, 1659, 3, '2013-10-20 19:30:31'),
(27, 2, 13, 1, 2225, 678, 3, '2013-10-20 19:30:31'),
(26, 14, 12, 1, 222, 2150, 3, '2013-10-20 19:30:31'),
(25, 6, 15, 1, 2046, 287, 3, '2013-10-20 19:30:31');

INSERT INTO {$tUserTournament} (`UserTournament_idUser`, `UserTournament_idTournament`, `joinDateUserTournament`) VALUES
(2, 1, '2013-10-20 11:33:35'),
(4, 1, '2013-10-20 11:33:35'),
(6, 1, '2013-10-20 11:33:35'),
(8, 1, '2013-10-20 11:33:35'),
(10, 1, '2013-10-20 11:33:35'),
(11, 1, '2013-10-20 11:33:35'),
(12, 1, '2013-10-20 11:33:35'),
(13, 1, '2013-10-20 11:33:35'),
(14, 1, '2013-10-20 11:33:35'),
(15, 1, '2013-10-20 11:33:35');

EOD;

//--SET @aTournamentId = 0;
//--CALL {$spCreateTournament}(1, 'Here', 3, 'Swiss', true, 1000, NOW(), NOW(), "internalwinner", false, null, @aTournamentId);
//--SELECT @aTournamentId AS id;
//
//UserTournament_idUser INT NOT NULL,
//  UserTournament_idTournament INT NOT NULL,
//
//  FOREIGN KEY (UserTournament_idUser) REFERENCES {$tUser}(idUser),
//  FOREIGN KEY (UserTournament_idTournament) REFERENCES {$tTournament}(idTournament),
//
//  PRIMARY KEY (UserTournament_idUser, UserTournament_idTournament),
//
//  -- Attributes
//  joinDateUserTournament DATETIME NOT NULL

?>