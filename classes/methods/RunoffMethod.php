<?php

class RunoffMethod extends SingleWinnerMethod {
    
    private $ranking = array();
    
    public function buildForm($form_values) {
        return array(
            'elimination_method' => array(
                '#title' => t('Elimination Method'),
                '#description' => t('The method used to eliminate candidates at each round.'),
                '#type' => 'select',
                '#required' => TRUE,
                '#options' => array(
                    'lowest' => t('Eliminate lowest candidate'),
                    'contingent' => t('Eliminate all but two candidates (Contingent)'),
                    'multiple' => t('Eliminate multiple candidates (Batch Elimination)'),
                ),
                '#default_value' => isset($form_values['elimination_method']) ? $form_values['elimination_method'] : 'lowest',
            ),
        );
    }
    
    public function runElection() {
        $this->ranking = array();
        $continue = TRUE;
        $last = NULL;
        while ($continue) {
            $ranking = $this->ballotRound();
            $majority = floor(array_sum($ranking) / 2) + 1;
            if (reset($ranking) >= $majority || $last === count($ranking)) {
                $this->ranking = array_merge($ranking, $this->ranking);
                $continue = FALSE;
            }
            else {
                $this->eliminateCandidates($ranking);
            }
            $last = count($ranking);
        }
        arsort($this->ranking);
    }
    
    private function eliminateCandidates($ranking) {
        $eliminations = array();
        switch ($this->getSetting('elimination_method')) {
            case 'contingent':
                arsort($ranking);
                $keep = 2;
                foreach ($ranking as $can => $score) {
                    if ($keep > 0) { $keep--; }
                    else {
                        $eliminations[] = $can;
                    }
                }
                break;
            case 'multiple':
                $eliminations = $this->determineBatchElimination($ranking);
                break;
            default:
            case 'lowest':
                $min = min($ranking);
                foreach ($ranking as $can => $score) {
                    if ($score === $min) {
                        $eliminations[] = $can;
                    }
                }
                break;
        }
        foreach ($eliminations as $eliminate) {
            $this->ranking[$eliminate] = $ranking[$eliminate];
            $this->eliminateCandidate($eliminate);
        }
    }
    
    private function determineBatchElimination($ranking) {
        asort($ranking);
        $candidates = array_keys($ranking);
        $eliminate = array();
        $total = 0;
        for ($k = 0; $k < count($candidates) - 1; $k++) {
            if ($total + $ranking[$candidates[$k]] < $ranking[$candidates[$k + 1]]) {
                $total += $ranking[$candidates[$k]];
                $eliminate[] = $candidates[$k];
            }
        }
        return $eliminate;
    }
    
    private function ballotRound() {
        $ranking = array();
        foreach ($this->getVotes() as $vote) {
            if ($vote instanceof AbstractBallot) {
                $candidate = $vote->mostPreferred($this->getEliminatedCandidates());
                if (!empty($candidate)) {
                    if (!isset($ranking[$candidate])) {
                        $ranking[$candidate] = 0;
                    }
                    $ranking[$candidate] += $vote->getVotes();
                }
            }
        }
        arsort($ranking);
        return $ranking;
    }
    
    public function getOrdering() {
        return $this->ranking;
    }
    
}
