<?php

abstract class SingleWinnerMethod {
    
    private $values = array();
    
    private $votes = array();
    
    private $eliminated = array();
    
    public function eliminateCandidate($candidate) {
        $this->eliminated[] = $candidate;
    }
    
    public function getEliminatedCandidates() {
        return $this->eliminated;
    }
    
    public function reset() {
        $this->eliminated = array();
        $this->values = array();
        $this->votes = array();
    }
    
    public function limitPool($candidates) {
        $all = $this->getAllCandidates();
        foreach ($all as $can) {
            if (!in_array($can, $candidates)) {
                $this->eliminateCandidate($can);
            }
        }
    }
    
    public function getAllCandidates() {
        $candidates = array();
        foreach ($this->getVotes() as $vote) {
            if ($vote instanceof AbstractBallot) {
                foreach ($vote->getAllCandidates() as $can) {
                    if (!in_array($can, $candidates)) {
                        $candidates[] = $can;
                    }
                }
            }
        }
        return $candidates;
    }
    
    public function buildForm($form_values) {
        return array();
    }
    
    public function setVotes($votes) {
        $this->votes = $votes;
    }
    
    public function getVotes() {
        return $this->votes;
    }
    
    public function setSettings($formValues) {
        $this->values = $formValues;
    }
    
    public function getSetting($key) {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return NULL;
    }
    
    public function getWinners() {
        $ordering = $this->getOrdering();
        if (empty($ordering)) {
            return FALSE;
        }
        $max = max($ordering);
        $winners = array();
        foreach ($ordering as $candidate => $score) {
            if ($score === $max) {
                $winners[] = $candidate;
            }
        }
        return $winners;
    }
    
    public abstract function runElection();
    
    public abstract function getOrdering();
    
}
