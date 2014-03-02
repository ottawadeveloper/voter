<?php

class CondorcetMethod extends SingleWinnerMethod {
    
    private $ranking = array();
    
    private $pairwise = array();
    
    public function buildForm($form_values) {
        return array(
            'fallback-set' => array(
                '#title' => t('Fallback Set'),
                '#type' => 'select',
                '#description' => t('The result set to use if a Condorcet winner is not found'),
                '#required' => TRUE,
                '#options' => array(
                    'smith' => t('Smith Set'),
                    'schwartz' => t('Schwartz Set'),
                ),
                '#default_value' => isset($form_values['fallback-set']) ? $form_values['fallback-set'] : 'smith',
            ),
        );
    }
    
    public function runElection() {
        $pairwise = $this->calculatePairwiseMatrix();
        $candidates = $this->getAllCandidates();
        $max = count($candidates) - 1;
        $win = TRUE;
        foreach ($candidates as $candidate) {
            $wins = 0;
            foreach ($candidates as $opponent) {
                if ($candidate !== $opponent) {
                    if ($pairwise[$candidate][$opponent] > $pairwise[$opponent][$candidate]) {
                        $wins++;
                    }
                }
            }
            if ($wins === $max) {
                $this->ranking[$candidate] = 1;
                break;
            }
        }
        if (empty($this->ranking)) {
            $set = array();
            switch ($this->getSetting('fallback-set')) {
                case 'schwartz':
                    $set = $this->calculateSmithSet();
                default:
                case 'smith':
                    $set = $this->calculateSchwartzSet();
            }
            foreach ($set as $candidate) {
                $this->ranking[$candidate] = 1;
            }
        }
        foreach ($this->getAllCandidates() as $candidate) {
            if (!isset($this->ranking[$candidate])) {
                $this->ranking[$candidate] = 0;
            }
        }
        arsort($this->ranking);
    }
    
    protected function calculatePairwiseMatrix() {
        if (!empty($this->pairwise)) {
            return $this->pairwise;
        }
        $pairwise = array();
        $candidates = $this->getAllCandidates();
        foreach ($this->getVotes() as $vote) {
            if ($vote instanceof AbstractBallot) {
                foreach ($candidates as $candidate) {
                    $pairwise[$candidate] = array();
                    foreach ($candidates as $opponent) {
                        $pairwise[$candidate][$opponent] = 0;
                        if ($candidate !== $opponent) {
                            if ($vote->checkPairwise($candidate, $opponent)) {
                                $pairwise[$candidate][$opponent] += $vote->getVotes();
                            }
                        }
                    }
                }
            }
        }
        $this->pairwise = $pairwise;
        return $pairwise;
    }
    
    public function getOrdering() {
        arsort($this->ranking);
        return $this->ranking;
    }
    
  protected function calculateSchwartzSet() {
    $pairings = $this->calculatePairwiseMatrix();
    $candidates = $this->getAllCandidates();
    $relation = array();
    foreach ($candidates as $x) {
      $relation[$x] = array();
      foreach ($candidates as $y) {
        if ($x !== $y) {
          $relation[$x][$y] = $pairings[$x][$y] > $pairings[$y][$x];
        }
      }
    }
    return $this->kosarajuMaximalSet($relation, $candidates);
  }
  
  protected function calculateSmithSet() {
    $pairings = $this->calculatePairwiseMatrix();
    $candidates = $this->getAllCandidates();
    $relation = array();
    foreach ($candidates as $x) {
      $relation[$x] = array();
      foreach ($candidates as $y) {
        if ($x !== $y) {
          $relation[$x][$y] = $pairings[$x][$y] >= $pairings[$y][$x];
        }
      }
    }
    return $this->kosarajuMaximalSet($relation, $candidates);
  }
  
  protected function kosarajuMaximalSet(array $originalRelation, $candidates) {
    $maximalSet = array();
    $searchOrder = array();
    $k = 1;
    $candidateMapping = array();
    foreach ($candidates as $i) {
      $candidateMapping[$k] = $i;
      $searchOrder[$k] = $k;
      $k++;
    }
    $relation = array();
    foreach ($candidateMapping as $new => $old) {
      $relation[$new] = array();
      foreach ($candidateMapping as $newj => $oldj) {
        if ($new != $newj) {
          $relation[$new][$newj] = $originalRelation[$old][$oldj];
        }
      }
    }
    $context = $this->depthFirstSearch($relation, $searchOrder);
    
    $nextSearchOrder = array();
    for ($k = 1; $k <= count($candidates); $k++) {
      $nextSearchOrder[$k] = $context['finishOrder'][count($candidates) + 1 - $k];
    }
    $transposedRelation = array();
    foreach ($candidateMapping as $new => $old) {
      $transposedRelation[$k] = array();
      foreach ($candidateMapping as $newj => $oldj) {
        if ($new != $newj) {
          $transposedRelation[$new][$newj] = $relation[$newj][$new];
        }
      }
    }
    
    $context = $this->depthFirstSearch($transposedRelation, $nextSearchOrder);
    foreach ($candidateMapping as $num => $candidate) {
      if (!$context['treeConnects'][$num]) {
        $maximalSet[] = $candidate;
      }
    }
    return $maximalSet;
  }
  
  protected function depthFirstSearch($relation, $searchOrder) {
    $context = array(
      'finishOrder' => array(),
      'finishOrderCount' => 0,
      'visited' => array(),
      'tree' => array(),
      'treeConnects' => array(),
      'treeCount' => 0,
    );
    foreach ($searchOrder as $index) {
      $context['visited'][$index] = FALSE;
      $context['treeConnects'][$index] = FALSE;
    }
    foreach ($searchOrder as $index => $rootIndex) {
      if (!$context['visited'][$rootIndex]) {
        $context['treeCount']++;
        $this->visitNode($relation, $searchOrder, $rootIndex, $context);
      }
    }
    return $context;
  }
  
  protected function visitNode($relation, $order, $visitIndex, &$context) {
    $context['tree'][$visitIndex] = $context['treeCount'];
    $context['visited'][$visitIndex] = TRUE;
    foreach ($order as $index => $probeIndex) {
      if (!empty($relation[$visitIndex][$probeIndex])) {
        if (empty($context['visited'][$probeIndex])) {
          $this->visitNode($relation, $order, $probeIndex, $context);
        }
        else {
          if ($context['tree'][$probeIndex] < $context['treeCount']) {
            $context['treeConnects'][$context['treeCount']] = TRUE;
          }
        }
      }
    }
    $context['finishOrderCount']++;
    $context['finishOrder'][$context['finishOrderCount']] = $visitIndex;
  }
  
}

