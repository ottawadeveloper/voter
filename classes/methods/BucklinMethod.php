<?php

class BucklinMethod extends SingleWinnerMethod {
    
    private $ranking = array();
    
    public function runElection() {
        $this->ranking = array();
        $round = 0;
        $continue = TRUE;
        $total = 0;
        $majority = 0;
        while ($continue) {
            $continue = FALSE;
            foreach ($this->getVotes() as $vote) {
                if ($round === 0) { $total += $vote->getVotes(); }
                if ($vote instanceof AbstractBallot) {
                    $candidates = $vote->getOrderedCandidates();
                    if (isset($candidates[$round])) {
                        foreach ($candidates[$round] as $candidate) {
                            if (!isset($this->ranking[$candidate])) {
                                $this->ranking[$candidate] = 0;
                            }
                            $this->ranking[$candidate] += $vote->getVotes();
                            $continue = TRUE;
                        }
                    }
                }
            }
            arsort($this->ranking);
            if ($round === 0) {
                $majority = floor($total / 2) + 1;
            }
            if (max($this->ranking) > $majority) {
                $continue = FALSE;
            }
            $round++;
        }
    }
    
    public function getOrdering() {
        return $this->ranking;
    }
    
}
