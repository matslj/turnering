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
    
    public function getTournaments($theDatabase, $byUser = false) {
        
        self::$LOG -> debug(" **** In CTournamentManager in getAllTournaments() **** ");
        
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
    
         while($row = $res->fetch_object()) {
             $t = CTournament::getEmptyInstance();
             self::$LOG -> debug(" **** In aaa() **** ");
             $t->setId($row->id);
             self::$LOG -> debug(" **** In bbb() **** ");
             $t->setCreator($row->creator);
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