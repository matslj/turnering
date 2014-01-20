<?php
// ===========================================================================================
//
// SQLCreateImageAndPageTables.php
//
// SQL statements to create the tables for Sida (the page text editing stuff)
//
// WARNING: Do not forget to check input variables for SQL injections.
//
// Author: Mats Ljungquist
//

// Get the tablenames
$tSida                  = DBT_Sida;
$tUser 			        = DBT_User;
$tGroup 		        = DBT_Group;
$tGroupMember           = DBT_GroupMember;
$tTournament            = DBT_Tournament;

// Get the SP names
$spPInsertOrUpdateSida	= DBSP_PInsertOrUpdateSida;
$spPGetSidaDetails   	= DBSP_PGetSidaDetails;
$spPGetSidaDetailsById  = DBSP_PGetSidaDetailsById;

// Get the UDF names
$udfFCheckUserIsOwnerOrAdminOfSida = DBUDF_FCheckUserIsOwnerOrAdmin;

// Create the query
$query = <<<EOD
  
--
-- Table for Sida
--
DROP TABLE IF EXISTS {$tSida};
CREATE TABLE {$tSida} (

  -- Primary key(s)
  idSida INT AUTO_INCREMENT NOT NULL PRIMARY KEY,

  -- Foreign keys
  Sida_idUser INT NOT NULL,
  tRefSida_idTournament INT NULL,
  FOREIGN KEY (Sida_idUser) REFERENCES {$tUser}(idUser),
  FOREIGN KEY (tRefSida_idTournament) REFERENCES {$tTournament}(idTournament),

  -- Attributes
  pageNameSida VARCHAR(100) NOT NULL,
  titleSida VARCHAR(256) NOT NULL,
  contentSida BLOB NOT NULL,
  createdSida DATETIME NOT NULL,
  modifiedSida DATETIME NULL
);

--
-- SP to insert or update article
-- If article id is 0 then insert, else update
--
DROP PROCEDURE IF EXISTS {$spPInsertOrUpdateSida};
CREATE PROCEDURE {$spPInsertOrUpdateSida}
(
	INOUT aSidaId INT,
	IN aUserId INT,
    IN aPageName VARCHAR(100),
	IN aTitle VARCHAR(256),
	IN aContent BLOB,
    IN aTournamentId INT
)
BEGIN
    DECLARE theTId INT;
        
    IF aTournamentId = 0 THEN
        SET theTId = null;
    ELSE
        SET theTId = aTournamentId;
    END IF;
   
	IF aSidaId = 0 THEN
	BEGIN
		INSERT INTO {$tSida}
			(Sida_idUser, pageNameSida, titleSida, contentSida, createdSida, tRefSida_idTournament)
			VALUES
			(aUserId, aPageName, aTitle, aContent, NOW(), theTId);
		SET aSidaId = LAST_INSERT_ID();
	END;
	ELSE
	BEGIN
		UPDATE {$tSida} SET
			titleSida       = aTitle,
			contentSida 	= aContent,
			modifiedSida	= NOW()
		WHERE
			idSida = aSidaId  AND
			{$udfFCheckUserIsOwnerOrAdminOfSida}(aSidaId, aUserId)
		LIMIT 1;
	END;
	END IF;
END;

--
-- SP to get the contents of an article
--
DROP PROCEDURE IF EXISTS {$spPGetSidaDetails};
CREATE PROCEDURE {$spPGetSidaDetails}
(
	IN aPageName VARCHAR(100),
    IN aTournamentID INT
)
BEGIN
    IF aTournamentID = 0 THEN
    BEGIN
        SELECT
            A.idSida AS id,
            A.titleSida AS title,
            A.contentSida AS content,
            A.createdSida AS created,
            A.modifiedSida AS modified,
            COALESCE(A.modifiedSida, A.createdSida) AS latest,
            U.nameUser AS username,
                    A.Sida_idUser AS userId
        FROM {$tSida} AS A
            INNER JOIN {$tUser} AS U
                ON A.Sida_idUser = U.idUser
        WHERE
            pageNameSida = aPageName
            LIMIT 1;
    END;
    ELSE
    BEGIN
        SELECT
            A.idSida AS id,
            A.titleSida AS title,
            A.contentSida AS content,
            A.createdSida AS created,
            A.modifiedSida AS modified,
            COALESCE(A.modifiedSida, A.createdSida) AS latest,
            U.nameUser AS username,
                    A.Sida_idUser AS userId
        FROM {$tSida} AS A
            INNER JOIN {$tUser} AS U
                ON A.Sida_idUser = U.idUser
        WHERE
            pageNameSida = aPageName AND
            tRefSida_idTournament = aTournamentId
            LIMIT 1;
    END;
    END IF;
END;
                
--
-- SP to get the contents of an article
--
DROP PROCEDURE IF EXISTS {$spPGetSidaDetailsById};
CREATE PROCEDURE {$spPGetSidaDetailsById}
(
	IN aPageId INT
)
BEGIN
	SELECT
                A.idSida AS id,
		A.titleSida AS title,
		A.contentSida AS content,
		A.createdSida AS created,
		A.modifiedSida AS modified,
		COALESCE(A.modifiedSida, A.createdSida) AS latest,
		U.nameUser AS username,
                A.Sida_idUser AS userId
	FROM {$tSida} AS A
		INNER JOIN {$tUser} AS U
			ON A.Sida_idUser = U.idUser
	WHERE
		A.idSida = aPageId
        LIMIT 1;
END;

--
--  Create UDF that checks if user owns article or is member of group adm.
--
DROP FUNCTION IF EXISTS {$udfFCheckUserIsOwnerOrAdminOfSida};
CREATE FUNCTION {$udfFCheckUserIsOwnerOrAdminOfSida}
(
	aSidaId INT,
	aUserId INT
)
RETURNS BOOLEAN
READS SQL DATA
BEGIN
	DECLARE isAdmin INT;
	DECLARE isOwner INT;

	SELECT idUser INTO isAdmin
	FROM {$tUser} AS U
		INNER JOIN {$tGroupMember} AS GM
			ON U.idUser = GM.GroupMember_idUser
		INNER JOIN {$tGroup} AS G
			ON G.idGroup = GM.GroupMember_idGroup
	WHERE
		idGroup = 'adm' AND
		idUser = aUserId;

	SELECT idUser INTO isOwner
	FROM {$tUser} AS U
		INNER JOIN {$tSida} AS A
			ON U.idUser = A.Sida_idUser
	WHERE
		idSida = aSidaId AND
		idUser = aUserId;

	RETURN (isAdmin OR isOwner);
END;
                
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
--
-- Insert some default pages
--

SET @aSidaId = 0;
CALL {$spPInsertOrUpdateSida}(@aSidaId, 2, 'PHome.php', 'Vad är det här?', 'Den här webplatsen är ett verktyg för administration av warhammer fantasy battle turneringar. Vem som helst, som är upplagd som användare i systemet, kan skapa turneringar. <br /><br />Features:<br /><ul><li>Turneringsmodell swiss (första rundan randomiserad och i nästkommande runda är det de två bästa som möts, förutsatt att de inte mötts tidigare).</li><li>Proxy-filter - översätter från ett poängintervall till ett annat (exempelvis, en poängdifferans på 100 kan innebära 10 - 10 i omgångsresultat).</li><li>Ett flertal tie-breakers - om två spelare ligger på samma poängnivå, så kan man låta deras placering avgöras av tie-breakers, t.ex så kan man bestämma att om de tidigare har mötts i turneringen, så blir den högre rankad som vann det mötet.</li><li>Bye score - om en spelare tvingas stå över en runda (för att man är ett ojämt antal spelare), så kan man arrangera så att denne får ett bestämt antal poäng som tröst. Dessutom är systemet byggt så att ingen ska behöva bli byed ytterligare en gång i en turnering om inte alla andra också har blivit byed en gång.</li></ul>', 0);
SET @aSidaId = 0;
CALL {$spPInsertOrUpdateSida}(@aSidaId, 2, 'PAdminIndex.php', 'Ändra mig', 'Ändra mig', 0);

EOD;


?>