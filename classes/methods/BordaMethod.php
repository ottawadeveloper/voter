<?php

class BordaMethod extends SingleWinnerMethod {
    
    private $ranking = array();
    
    public function buildForm($form_values) {
        return array(
            'points' => array(
                '#title' => t('Point System'),
                '#type' => 'select',
                '#options' => array(
                    'simple' => t('Simple (5,4,3,2,1)'),
                    'double' => t('Doubled (10,8,6,4,2)'),
                    'offset' => t('Offset (4,3,2,1,0)'),
                    'diminishing' => t('Diminishing (1, 0.5, 0.33, 0.25, 0.2'),
                    'modified' => t('Modified (X, X-1, ..., 1), X is number of candidates ranked'),
                ),
                '#required' => TRUE,
                '#default_value' => isset($form_values['points']) ? $form_values['points'] : 'simple',
            ),
        );
    }
    
    public function runElection() {
        $this->ranking = $this->bordaCount();
        arsort($this->ranking);
    }
    
    protected function bordaCount() {
        $ranking = array();
        $allCandidates = $this->getAllCandidates();
        $total = count($allCandidates);
        $skip = $this->getEliminatedCandidates();
        $total -= count($skip);
        foreach ($this->getVotes() as $vote) {
            if ($vote instanceof AbstractBallot) {
                $ballotRanked = 0;
                foreach ($vote->getAllCandidates() as $can) {
                    if (!in_array($can, $skip)) {
                        $ballotRanked++;
                    }
                }
                $rankedCandidates = array();
                foreach ($vote->getOrderedCandidates() as $rank => $canList) {
                    foreach ($canList as $can) {
                        if (!in_array($can, $skip)) {
                            if (!isset($rankedCandidates[$rank])) {
                                $rankedCandidates[$rank] = array();
                            }
                            $rankedCandidates[$rank][] = $can;
                        }
                    }
                }
                $rankedCandidates = array_values($rankedCandidates);
                foreach ($rankedCandidates as $rank => $candidates) {
                    foreach ($candidates as $can) {       
                        if (!isset($ranking[$can])) {
                            $ranking[$can] = 0;
                        }
                        $ranking[$can] += $this->getPoints($rank, $total, $ballotRanked) * $vote->getVotes();
                    }
                    foreach ($allCandidates as $candidate) {
                        if (!(in_array($candidate, $candidates) || in_array($candidate, $skip))) {
                            if (!isset($ranking[$candidate])) {
                                $ranking[$candidate] = 0;
                            }
                            $ranking[$candidate] += $this->getPoints($total - 1, $total, $ballotRanked) * $vote->getVotes();
                        }
                    }
                }
            }
        }
        return $ranking;
    }
    
    private function getPoints($position, $total, $ranked) {
        $pts = 0;
        switch ($this->getSetting('points')) {
            case 'offset':
                $pts = $total - $position - 1;
            case 'double':
                $pts = 2 * ($total - $position);
            case 'diminishing':
                $pts = 1 / ($position + 1);
            case 'modified':
                $pts = $ranked - $position;
            default:
            case 'simple':
                $pts = $total - $position;
        }
        if ($pts < 0) {
            $pts = 0;
        }
        return $pts;
    }
    
    public function getOrdering() {
        return $this->ranking;
    }
    
}
