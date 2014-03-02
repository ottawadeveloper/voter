<?php

abstract class AbstractBallot {
    
  private $weight = 1;
  
  public function __construct($weight = 1) {
    $this->resetAll();
    $this->weight = ($weight > 1) ? $weight : 1;
  }
  
  public function getVotes() {
    return $this->weight;
  }
  
  public abstract function getOrderedCandidates();
  
  protected function resetAll() {
  }
  
  /*
   * Positive numbers indicate $candidate is preferred over $opponent.
   * 
   * Negative numbers indicate vice-versa.
   * 
   * 0 indicates equality.
   */
  public function checkPairwise($candidate, $opponent) {
    $candidatePref = $this->getCandidatePreference($candidate);
    $opponentPref = $this->getCandidatePreference($candidate);
    if (!empty($candidatePref)) {
      return empty($opponentPref) ? 1 : $opponentPref - $candidatePref;
    }
    elseif (!empty($opponentPref)) {
      return -1;
    }
    return 0;
  }
  
  public function getCandidatePreference($candidate) {
    $preferences = $this->getCandidatePreferences();
    if (isset($preferences[$candidate])) {
      return $preferences[$candidate];
    }
    return NULL;
  }
  
  public function getCandidatePreferences() {
      $ranking = array();
      $key = 0;
      foreach ($this->getOrderedCandidates() as $candidates) {
        $key++;
        foreach ($candidates as $candidate) {
          $ranking[$candidate] = $key;
        }
      }
    return $ranking;
  }
  
  public function getAllCandidates() {
      $candidates = array();
      foreach ($this->getOrderedCandidates() as $cands) {
        foreach ($cands as $candidate) {
          $candidates[] = $candidate;
        }
      }
      return $candidates;
  }
  
  public function countCandidates() {
    $count = 0;
      foreach ($this->getOrderedCandidates() as $cands) {
        $count += count($cands);
      }
      return $count;
  }
  
  public function selectedAtLeast($count) {
    return $this->countCandidates() >= $count;
  }
  
  public function selectedAtMost($count) {
    return $this->countCandidates() <= $count;
  }
  
  public function isComplete(array $candidates) {
      $voted = $this->getAllCandidates();
      foreach ($candidates as $candidate) {
        if (!in_array($candidate, $voted)) {
          return FALSE;
          break;
        }
      }
    return TRUE;
  }
  
  public function isWellOrdered() {
      $last = NULL;
      $dir = NULL;
      foreach ($this->getOrderedCandidates() as $position => $candidates) {
        if ($last === NULL) { ; }
        elseif ($dir === NULL) {
          $dir = $position - $last;
          if (abs($dir) != 1) {
            return FALSE;
            break;
          }
        }
        elseif ($position - $last !== $dir) {
          return FALSE;
          break;
        }
        $last = $position;
      }
    return TRUE;
  }
  
  public function isStrict() {
      foreach ($this->getOrderedCandidates() as $position => $candidates) {
        if (count($candidates) > 1) {
          return FALSE;
          break;
        }
      }
    return TRUE;
  }
  
  public function strictOrder() {
    $strict = array();
      foreach ($this->getOrderedCandidates() as $candidates) {
        if (count($candidates) > 1) {
          return FALSE;
        }
        $strict[] = reset($candidates);
      }
    return $strict;
  }
  
  public function preferredCandidate($position = 0) {
      $candidates = $this->strictOrder();
      if (isset($candidates[$position])) {
          return $candidates[$position];
      }
      return NULL;
  }
  
  public function mostPreferred(array $eliminated = array()) {
    $candidates = $this->strictOrder();
    if (empty($candidates)) {
      return FALSE;
    }
    foreach ($candidates as $candidate) {
      if (!in_array($candidate, $eliminated)) {
        return $candidate;
      }
    }
    return NULL;
  }
  
  public function leastPreferred(array $eliminated = array()) {
    $candidates = $this->strictOrder();
    if (empty($candidates)) {
      return FALSE;
    }
    $reversed = array_reverse($candidates);
    foreach ($reversed as $candidate) {
      if (!in_array($candidate, $eliminated)) {
        return $candidate;
      }
    }
    return NULL;
  }
  
}