<?php

class BordaRunoff extends BordaMethod {
    
    private $ranking = array();
    
    public function buildForm($form_values) {
        return parent::buildForm($form_values) + array(
            'elimination_method' => array(
                '#title' => t('Elimination Method'),
                '#description' => t('The method used to eliminate candidates at each round.'),
                '#type' => 'select',
                '#required' => TRUE,
                '#options' => array(
                    'lowest' => t('Eliminate lowest candidate (Baldwin)'),
                    'multiple' => t('Eliminate below average candidates (Nanson)'),
                ),
                '#default_value' => isset($form_values['elimination_method']) ? $form_values['elimination_method'] : 'multiple',
            ),
        );
    }
    
    public function getOrdering() {
        return $this->ranking;
    }
    
    public function runElection() {
        $this->ranking = array();
        $continue = TRUE;
        $last = NULL;
        while ($continue) {
            $ballots = $this->bordaCount();
            if (count($ballots) > 1 && ($last !== count($ballots))) {
                $this->eliminateCandidates($ballots);
            }
            else {
                $start = count($this->ranking);
                $last = 0;
                foreach ($ballots as $can => $score) {
                    if ($score > $last) {
                        $start++;
                    }
                    $this->ranking[$can] = $start;
                    $last = $score;
                }
                $continue = FALSE;
            }
            $last = count($ballots);
        }
    }
    
    private function eliminateCandidates($ranking) {
        $eliminated = array();
        switch ($this->getSetting('elimination_method')) {
            case 'multiple':
                $avg = array_sum($ranking) / count($ranking);
                foreach ($ranking as $can => $score) {
                    if ($score < $avg) {
                        $eliminated[] = $can;
                    }
                }
                break;
            default:
            case 'lowest':
                $min = min($ranking);
                foreach ($ranking as $can => $score) {
                    if ($score === $min) {
                        $eliminated[] = $can;
                    }
                }
                break;
        }
        $pos = count($this->ranking) + 1;
        foreach ($eliminated as $can) {
            $this->ranking[$can] = $pos;
            $this->eliminateCandidate($can);
        }
    }
    
}