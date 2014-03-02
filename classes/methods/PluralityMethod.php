<?php

class PluralityMethod extends SingleWinnerMethod {
    
    private $ranking = array();
    
    public function runElection() {
        $this->ranking = array();
        foreach ($this->getVotes() as $vote) {
            if ($vote instanceof AbstractBallot) {
                $candidate = $vote->mostPreferred($this->getEliminatedCandidates());
                if (!isset($this->ranking[$candidate])) {
                    $this->ranking[$candidate] = 0;
                }
                $this->ranking[$candidate] += $vote->getVotes();
            }
        }
        arsort($this->ranking);
    }
    
    public function getOrdering() {
        return $this->ranking;
    }
    
}
