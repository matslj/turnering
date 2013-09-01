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
    
    public function getTournamentMatchupsAsHtml($theDatabase, $theRound) {

        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        $tempTournament = $this->getTournament($theDatabase);
        $tempTournament->createOrRecreateRound($theDatabase, $theRound);
        return $tempTournament->getAllRoundsAsHtml();
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