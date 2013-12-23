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
    
    public function getTournamentMatchupsAsHtml($theDatabase, $admin) {

        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        $tempTournament = $this->getTournament($theDatabase);
        $tempTournament->createOrRecreateRound($theDatabase);
        return $tempTournament->getAllRoundsAsHtml($admin);
    }
    
    public function modifyRound($theDatabase, $theRound) {

        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        $tempTournament = $this->getTournament($theDatabase);
        $tempTournament->createOrRecreateRound($theDatabase, $theRound);
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
    
    public function createTournament($theDatabase) {
//        $tournament = $this->getActiveTournament($theDatabase);
//        if (!empty($tournament)) {
//            return $tournament;
//        } else {
            $uo = CUserData::getInstance();
            $userId = $uo -> getId();
            $spCreateTournament = DBSP_CreateTournament;
            $query = "CALL {$spCreateTournament}({$userId}, '', 3, 'Swiss', true, 1000, NOW(), NOW(), 'internalwinner', false, null, @aTournamentId);";
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
            
            $t = CTournament::getInstanceByParameters($tId, $uo, "", 3, "Swiss", 1, 1000, null, null, null, 'internalwinner', 0, null);
            
            return $t;
//        }
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
            {$byUserHtml}
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
    
    public function getScoreboardAsHtml($theDatabase) {
        self::$LOG -> debug("dfdffdfdfd");
        $tempTournament = $this->getTournament($theDatabase);
        self::$LOG -> debug("yuyui");
        $participants = $tempTournament->getParticipantsSortedByScore($theDatabase);
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