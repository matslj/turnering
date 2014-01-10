<?php
/**
 * Convenience class. Avoid using the setters.
 * 
 * @author Mats Ljungquist
 */
class view_CParticipant {

    private $idUser;
    private $accountUser;
    private $nameUser;
    private $armyUser;
    
    public function __construct($idUser, $accountUser, $nameUser, $armyUser) {
        $this->idUser = $idUser;
        $this->accountUser = $accountUser;
        $this->nameUser = $nameUser;
        $this->armyUser = $armyUser;
    }
    
    // ------------------------------------------------------------------------------------
	//
	// Destructor
	//
	public function __destruct() {
	}

    public function getIdUser() {
        return $this->idUser;
    }

    public function setIdUser($idUser) {
        $this->idUser = $idUser;
    }

    public function getAccountUser() {
        return $this->accountUser;
    }

    public function setAccountUser($accountUser) {
        $this->accountUser = $accountUser;
    }

    public function getNameUser() {
        return $this->nameUser;
    }

    public function setNameUser($nameUser) {
        $this->nameUser = $nameUser;
    }

    public function getArmyUser() {
        return $this->armyUser;
    }

    public function setArmyUser($armyUser) {
        $this->armyUser = $armyUser;
    }

    public function toJson() {
        
        return array(
            "id" => $this->getIdUser(),
            "account" => $this->getAccountUser(),
            "army" => $this->getArmyUser(),
        );
    }

} // End of Of Class

?>