<?php
/**
 * Convenience class. Avoid using the setters.
 * 
 * @author Mats Ljungquist
 */
class view_CTournamentView {

    private $id;
    private $place;
    private $active;
    private $tournamentDateFrom; // class CDate
    private $tournamentDateTom;  // class CDate
    
    function __construct($id, $place, $theActive, $tournamentDateFrom, $tournamentDateTom) {
        $this->id = $id;
        $this->place = $place;
        $this->active = $theActive != 0 ? true : false;
        $this->tournamentDateFrom = CDate::getInstanceFromMysqlDatetime($tournamentDateFrom);
        $this->tournamentDateTom = CDate::getInstanceFromMysqlDatetime($tournamentDateTom);
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getPlace() {
        return $this->place;
    }

    public function setPlace($place) {
        $this->place = $place;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getTournamentDateFrom() {
        return $this->tournamentDateFrom;
    }

    public function setTournamentDateFrom($tournamentDateFrom) {
        $this->tournamentDateFrom = $tournamentDateFrom;
    }

    public function getTournamentDateTom() {
        return $this->tournamentDateTom;
    }

    public function setTournamentDateTom($tournamentDateTom) {
        $this->tournamentDateTom = $tournamentDateTom;
    }
    
    public function toJson() {
        
        return array(
            "id" => $this->getId(),
            "place" => $this->getPlace(),
            "active" => $this->getActive(),
            "fromDate" => $this->getTournamentDateFrom()->getDate(),
            "tomDate" => $this->getTournamentDateTom()->getDate(),
        );
    }

} // End of Of Class

?>