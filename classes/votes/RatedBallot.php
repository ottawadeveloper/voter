<?php

class RatedBallot extends AbstractBallot {
  
  private $ratings = array();
  
  private $orderedCandidates = array();
  
  public function __construct($weight = 1, array $ratings = array()) {
    parent::__construct($weight);  
    foreach ($ratings as $candidate => $rating) {
        $this->addRating($candidate, $rating);
    }
  }
  
  public function addRating($candidate, $rating) {
    $this->ratings[$candidate] = $rating;
    $this->resetAll();
  }
  
  public function resetAll() {
    $this->orderedCandidates = array();
  }
  
  public function getCandidateRating($candidate) {
    return $this->ratings[$candidate];
  }
  
  public function getOrderedCandidates() {
    if (empty($this->orderedCandidates)) {
      $this->orderedCandidates = array();
      foreach ($this->ratings as $candidate => $rating) {
        if (!isset($this->orderedCandidates[$rating])) {
          $this->orderedCandidates[$rating] = array();
        }
        $this->orderedCandidates[$rating][] = $candidate;
      }
      krsort($this->orderedCandidates);
    }
    return $this->orderedCandidates;
  }
  
}
