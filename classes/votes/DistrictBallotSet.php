<?php

class DistrictBallotSet {
    
    private $districtName = "";
    private $category = "";
    
    private $seats = 1;
    
    private $ballots = array();
    
    public function __construct($name, array $ballots, $seats = 1, $category = '') {
        $this->districtName = $name;
        $this->category = $category;
        $this->seats = $seats;
        $this->ballots = $ballots;
    }
    
    public function addBallot(AbstractBallot $ballot) {
        $this->ballots[] = $ballot;
    }
    
    public function getBallots() {
        return $this->ballots;
    }
    
    public function getSeats() {
        return $this->seats;
    }
    
    public function getDistrictName() {
        return $this->districtName;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    
}

