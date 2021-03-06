<?php

// ===========================================================================================
//
// Class: CTournamentManager
// 
// Author: Mats Ljungquist
//
class CTournamentManager {
    
    private $tournament = null;
    
    public static $LOG = null;
    
    public function __destruct() {
        ;
    }
    
    public function __construct() {
        self::$LOG = logging_CLogger::getInstance(__FILE__);
    }
    
    public function getTournament($theDatabase, $tournamentId = 1) {
        
        // Lazy init
        if (empty($this->tournament)) {
            // DB connection is required
            if (empty($theDatabase)) {
                return null;
            }
            $this->tournament = CTournament::getInstanceById($theDatabase, $tournamentId);
        }
        return $this->tournament;
    }
    
    public function getTournamentMatchupsAsHtml($theDatabase, $aTournament, $admin) {

        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        $aTournament->createOrRecreateRound($theDatabase);
        return $aTournament->getAllRoundsAsHtml($admin);
    }
    
    public function modifyRound($theDatabase, $aTournament, $theRound) {

        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        $aTournament->createOrRecreateRound($theDatabase, $theRound);
    }
    
    public static function mayEditTournament($theDatabase, $tournamentId) {
        
        $uo = CUserData::getInstance();
        $canEdit = $uo->isAdmin();
        
        if (!$canEdit) {

            // Get the tablenames
            $tTournament       = DBT_Tournament;

            $query = <<< EOD
                SELECT
                    creatorTournament_idUser AS creator
                FROM {$tTournament}
                WHERE idTournament = {$tournamentId}
                LIMIT 1;
EOD;

            $res = $theDatabase->Query($query);
            $row = $res->fetch_object();

            if (!empty($row)) {
                $canEdit = ($uo->getId() == $row->creator);
            }

            $res->close();
        }
        
        return $canEdit;
    }
    
    public function getActiveTournament($theDatabase) {

        $uo = CUserData::getInstance();
        $userId = $uo -> getId();
        
        // Get the tablenames
        $tTournament       = DBT_Tournament;

        $query = <<< EOD
            SELECT
                idTournament AS id,
                creatorTournament_idUser AS creator,
                placeTournament AS place, 
                roundsTournament AS rounds, 
                typeTournament AS type, 
                activeTournament AS active,
                byeScoreTournament AS byeScore,
                createdTournament AS creationDate,
                dateFromTournament AS dateFrom,
                dateTomTournament AS dateTom,
                tieBreakersTournament AS tieBreakers,
                useProxyTournament AS useProxy,
                jsonScoreProxyTournament as scoreFilter
            FROM {$tTournament}
            WHERE creatorTournament_idUser = {$userId}
                AND activeTournament = true
            ORDER BY dateFrom DESC
            LIMIT 1;
EOD;
            
        $res = $theDatabase->Query($query);
    
        $row = $res->fetch_object();
        
        $t = null;
        
        if (!empty($row)) {
            $t = CTournament::getInstanceByParameters($row->id, $uo, $row->place, $row->rounds, $row->type, $row->active, $row->byeScore, $row->dateFrom, $row->dateTom, $row->creationDate, $row->tieBreakers, $row->useProxy, $row->scoreFilter);
        }
        
        $res->close();
         
        return $t;
    }
    
    public static function isPartOfTournament($theDatabase, $theUserId, $theTournamentId) {
        
        if (empty($theUserId) || empty($theTournamentId)) {
            return false;
        }
        
        // Get the tablenames
        $tUserTournament       = DBT_UserTournament;

        $query = <<< EOD
            SELECT
                UserTournament_idUser
                UserTournament_idTournament
            FROM {$tUserTournament}
            WHERE 
                UserTournament_idUser = {$theUserId} AND
                UserTournament_idTournament = {$theTournamentId}
            LIMIT 1;
EOD;
            
        $res = $theDatabase->Query($query);
    
        $row = $res->fetch_object();
        
        $inTournament = false;
        
        if (!empty($row)) {
            $inTournament = true;
        }
        
        $res->close();
        
        return $inTournament;
    }
    
    public static function getParticipantList($theDatabase, $theTournamentId) {
        // self::$LOG = logging_CLogger::getInstance(__FILE__);
        $tUser = DBT_User;
        $tUserTournament = DBT_UserTournament;
        // $imgUrl = WS_IMAGES;
        $query = <<< EOD
            SELECT
                idUser,
                accountUser,
                nameUser,
                armyUser
            FROM {$tUser} AS U INNER JOIN {$tUserTournament} AS UT ON UserTournament_idUser = idUser
            WHERE U.deletedUser = FALSE
                  AND U.activeUser = TRUE AND
                  UT.UserTournament_idTournament = {$theTournamentId};
EOD;

        // Perform the query and manage results
        $result = $theDatabase->Query($query);
        $participantList = array();

        while($row = $result->fetch_object()) {
            $participantList[] = new view_CParticipant($row->idUser, $row->accountUser, $row -> nameUser, $row -> armyUser);
        }
        $result -> close();
        if (empty($participantList)) {
            return "[]"; 
        } else {;
            foreach ($participantList as &$value) {
                $value = $value->toJson();
            }
            return json_encode($participantList);
        }
    }
    
    public function createTournament($theDatabase) {
        self::$LOG -> debug(" **** In CTournamentManager in createTournament(db) **** ");
//        $tournament = $this->getActiveTournament($theDatabase);
//        if (!empty($tournament)) {
//            return $tournament;
//        } else {
            $uo = CUserData::getInstance();
            $userId = $uo -> getId();
            $spCreateTournament = DBSP_CreateTournament;
            $query = "CALL {$spCreateTournament}({$userId}, '', 3, 'Swiss', false, 1000, NOW(), NOW(), 'internalwinner', false, null, @aTournamentId);";
            $query .= "SELECT @aTournamentId AS id;";

            // Perform the query
            $res = $theDatabase->MultiQuery($query);

            // Use results
            $results = Array();
            $theDatabase->RetrieveAndStoreResultsFromMultiQuery($results);

            // Retrieve and update the id of the Match-object
            $row = $results[1]->fetch_object();
            $tId = $row->id;

            // Close the result set
            $results[1]->close();
            self::$LOG -> debug(" ---- In CTournamentManager in createTournament(db) - it went well up to here ");
            $t = CTournament::getInstanceByParameters($tId, $uo, "", 3, "Swiss", 0, 1000, null, null, null, 'internalwinner', 0, null);
            self::$LOG -> debug(" ---- In CTournamentManager in createTournament(db) - it went well up to here 2 ");
            return $t;
//        }
    }
    
    public function getEmptyTournament() {
        $uo = CUserData::getInstance();
        $t = CTournament::getInstanceByParameters(-1, $uo, "", 3, "Swiss", 0, 1000, null, null, null, 'internalwinner', 0, null);
        return $t;
    }
    
    public static function getTournamentsAsJSON($theDatabase, $byUser = false) {
        
        $byUserSQL = "";
        
        if ($byUser) {
            $uo = CUserData::getInstance();
            $userId = $uo -> getId();
            $byUserSQL = "WHERE creatorTournament_idUser = " . $userId;
        }
        
        // Get the tablenames
        $tTournament       = DBT_Tournament;
        $tMatch            = DBT_Match;

        $query = <<< EOD
            SELECT
                idTournament AS id,
                placeTournament AS place, 
                activeTournament AS active,
                dateFromTournament AS dateFrom,
                dateTomTournament AS dateTom,
                MAX(M.roundMatch) as playedRounds
            FROM {$tTournament} LEFT OUTER JOIN
                {$tMatch} AS M ON M.tRefMatch_idTournament = idTournament 
            {$byUserSQL}
            GROUP BY idTournament
            ORDER BY active DESC, dateFrom DESC
            LIMIT 20;
EOD;

        $res = $theDatabase->Query($query);
         
        $tournamentViewList = array();
         
        while($row = $res->fetch_object()) {
            $tournamentViewList[] = new view_CTournamentView($row->id, $row->place, $row->active, $row->dateFrom, $row->dateTom, $row->playedRounds);
        }
        $res->close();
        if (empty($tournamentViewList)) {
            return "[]"; 
        } else {;
            foreach ($tournamentViewList as &$value) {
                $value = $value->toJson();
            }
            return json_encode($tournamentViewList);
        }
    }
    
    public function getTournaments($theDatabase, $byUser = false) {
        
        self::$LOG -> debug(" **** In CTournamentManager in getTournaments() **** ");
        
        $byUserHtml = "";
        
        if ($byUser) {
            $uo = CUserData::getInstance();
            $userId = $uo -> getId();
            $byUserHtml = "WHERE creatorTournament_idUser = " . $userId;
        }
        
        // Get the tablenames
        $tTournament       = DBT_Tournament;
        $tMatch            = DBT_Match;

        $query = <<< EOD
            SELECT
                idTournament AS id,
                creatorTournament_idUser AS creator,
                placeTournament AS place, 
                roundsTournament AS rounds, 
                typeTournament AS type, 
                activeTournament AS active,
                byeScoreTournament AS byeScore,
                createdTournament AS creationDate,
                dateFromTournament AS dateFrom,
                dateTomTournament AS dateTom,
                tieBreakersTournament AS tieBreakers,
                useProxyTournament AS useProxy,
                jsonScoreProxyTournament as scoreFilter,
                MAX(M.roundMatch) as playedRounds
            FROM {$tTournament} LEFT OUTER JOIN
                {$tMatch} AS M ON M.tRefMatch_idTournament = idTournament 
            {$byUserHtml}
            GROUP BY idTournament
            ORDER BY active DESC, dateFrom DESC
            LIMIT 20;
EOD;

         $returnArray = array();
         $res = $theDatabase->Query($query);
    
         $users = user_CUserRepository::getInstance($theDatabase);
         
         while($row = $res->fetch_object()) {
             $t = CTournament::getEmptyInstance();
             self::$LOG -> debug(" **** In aaa() **** ");
             $t->setId($row->id);
             self::$LOG -> debug(" **** In bbb() **** ");
             $user = $users->getUser($row->creator);
             $t->setCreator($user);
             self::$LOG -> debug(" **** In ccc() **** ");
             $t->setPlace($row->place);
             self::$LOG -> debug(" **** In ddd() **** ");
             $t->setNrOfRounds($row->rounds);
             self::$LOG -> debug(" **** In eee() **** ");
             $t->setType($row->type);
             self::$LOG -> debug(" **** In fff() **** ");
             $active = $row->active != 0 ? true : false;
             $t->setActive($active);
             self::$LOG -> debug(" **** In ggg() **** ");
             $t->setByeScore($row->byeScore);
             self::$LOG -> debug(" **** In hhh() **** ");
             $t->setCreationDate(CDate::getInstanceFromMysqlDatetime($row->creationDate));
             $t->setTournamentDateFrom(CDate::getInstanceFromMysqlDatetime($row->dateFrom));
             $t->setTournamentDateTom(CDate::getInstanceFromMysqlDatetime($row->dateTom));
             $t->setPlayedNrOfRounds($row->playedRounds);
             self::$LOG -> debug(" **** In uuu() **** ");
             $returnArray[] = $t;
         }
self::$LOG -> debug(" **** In deewwwwrr() **** ");
         $res->close();
         
         return $returnArray;
    }
    
    public function deleteTournament($theDatabase, $theTournamentId) {
        $spDeleteTournament = DBSP_DeleteTournament;
        $queryDelete = "CALL {$spDeleteTournament}({$theTournamentId});";
        $resDel = $theDatabase->MultiQuery($queryDelete);
        $nrOfStatements = $theDatabase->RetrieveAndIgnoreResultsFromMultiQuery();
        
        if($nrOfStatements != 1) {
            // Delete not OK
            self::$LOG -> debug("ERROR: Kunde inte radera turnering med id: " . $theTournamentId . " - number of statements: " . $nrOfStatements);
        }
    }
    
    public function getScoreboardAsHtml($theDatabase, $theTournamentId = 1) {
        self::$LOG -> debug("dfdffdfdfd");
        $tempTournament = $this->getTournament($theDatabase, $theTournamentId);
        self::$LOG -> debug("yuyui");
        $participants = $tempTournament->getParticipantsSortedByScore($theDatabase, 0, true);
        $html = "";
        $i = 1;
        self::$LOG -> debug("ih tmlmojen");
        if (!empty($participants)) {
            $html .= "<div class='datagrid'><table id='scoreTable'>";
            $html .= "<thead><tr><th id='rankScoreTh'>#</th><th id='pointsScoreTh'>Poäng</th><th id='roundsPlayedScoreTh'>Matcher</th><th>Namn</th></tr></thead><tbody>";
            foreach ($participants as $value) {
                $trClass = ($i%2 == 0 ? "" : " class='alt'");
                $html .= "<tr{$trClass}>";
                $html .= "<td>{$i}</td>";
                $html .= "<td>{$value->getTotalScore()}</td>";
                $html .= "<td>{$tempTournament->numberOfRoundsPlayed($value)}</td>";
                $html .= "<td>{$value->getName()} ({$value->getArmy()})</td>";
                $html .= "</tr>";
                $i++;
            }
            $html .= "</tbody></table></div>";
        }
        return $html;
    }
        
} // End of Of Class

?>