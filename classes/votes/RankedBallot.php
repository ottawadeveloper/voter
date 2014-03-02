<?php

class RankedBallot extends AbstractBallot {
  
  private $ranking = array();
  
  public function __construct($weight = 1, array $ranks = array()) {
      parent::__construct($weight);
      foreach ($ranks as $candidate => $rank) {
          if (!isset($this->ranking[$rank])) {
              $this->ranking[$rank] = array();
          }
          $this->ranking[$rank][] = $candidate;
      }
      $this->resetAll();
  }
  
  public function addCandidate($candidate, $rank = NULL) {
    if ($rank === NULL) {
        if (empty($this->ranking)) {
            $rank = 1;
        }
        else {
            $rank = max($this->ranking) + 1;
        }
    }
    if (!isset($this->ranking[$rank])) {
      $this->ranking[$rank] = array();
    }
    if (is_array($candidate)) {
        $this->ranking[$rank] = array_merge($candidate, $this->ranking[$rank]);
    }
    else {
        $this->ranking[$rank][] = $candidate;
    }
    $this->resetAll();
  }
  
  protected function resetAll() {
    parent::resetAll();
  }
  
  public function getOrderedCandidates() {
      ksort($this->ranking);
      return array_values($this->ranking);
  }
  
}
