<?php

class SingleWinnerContest extends AbstractVotingContest {
    
    public function buildForm($form_values) {
        $electoralMethods = voter_get_methods('single-winner');
        $options = array();
        foreach ($electoralMethods as $method_name => $info) {
            $options[$method_name] = t($info['title']);
        }
        $form = parent::buildForm($form_values) + array(
            'ballots-csv' => array(
                '#title' => t('Ballots CSV'),
                '#description' => t('This should be a URL path to a CSV file that meets the requirements of the voting system.'),
                '#type' => 'textfield',
                '#default_value' => isset($form_values['ballots-csv']) ? $form_values['ballots-csv'] : '',
                '#required' => TRUE,
            ),
            'ballot-type' => array(
                '#title' => t('Ballot Type'),
                '#description' => t('Set this to conform to your data. Ranked ballots should have an order of candidates in the "Ranking" field separated by greater than and/or equal signs. Rated ballots should contain a "Ratings" field that is a comma-delimited set of Candidate=Rating pairs. Ratings should always be numeric.'),
                '#type' => 'select',
                '#required' => TRUE,
                '#default_value' => isset($form_values['ballot-type']) ? $form_values['ballot-type'] : 'ranked',
                '#options' => array(
                  'ranked' => t('Ranked Ballot'),
                  'rated' => t('Rated Ballot'),
                ),
            ),
            'primary-method' => array(
                '#title' => t('Primary Election Method'),
                '#description' => t('Select the primary way in which candidates will be elected.'),
                '#type' => 'select',
                '#options' => $options,
                '#empty_option' => t('Select one'),
                '#empty_value' => '',
                '#required' => TRUE,
                '#default_value' => isset($form_values['primary-method']) ? $form_values['primary-method'] : '',
                '#ajax' => array(
                    'callback' => 'voter_ajax_full_form',
                    'wrapper' => 'contest-settings',
                ),
            ),
            'primary_settings' => array(
                '#title' => t('Primary Method Settings'),
                '#type' => 'fieldset',
                '#collapsible' => TRUE,
                '#collapsed' => TRUE,
                '#access' => FALSE,
            ),
            'secondary-method' => array(
                '#title' => t('Secondary Election Method'),
                '#description' => t('Select the second way in which candidates will be elected.'),
                '#type' => 'select',
                '#options' => $options,
                '#required' => FALSE,
                '#empty_option' => t('None'),
                '#empty_value' => '',
                '#default_value' => isset($form_values['secondary-method']) ? $form_values['secondary-method'] : '',
                '#ajax' => array(
                    'callback' => 'voter_ajax_full_form',
                    'wrapper' => 'contest-settings',
                ),
            ),
            'secondary_settings' => array(
                '#title' => t('Secondary Method Settings'),
                '#type' => 'fieldset',
                '#collapsible' => TRUE,
                '#collapsed' => TRUE,
                '#access' => FALSE,
            ),            
            'tertiary-method' => array(
                '#title' => t('Tertiary Election Method'),
                '#description' => t('Select the tertiary way in which candidates will be elected.'),
                '#type' => 'select',
                '#options' => $options,
                '#required' => FALSE,
                '#empty_option' => t('None'),
                '#empty_value' => '',
                '#default_value' => isset($form_values['tertiary-method']) ? $form_values['tertiary-method'] : '',
                '#ajax' => array(
                    'callback' => 'voter_ajax_full_form',
                    'wrapper' => 'contest-settings',
                ),
            ),
            'tertiary_settings' => array(
                '#title' => t('Tertiary Method Settings'),
                '#type' => 'fieldset',
                '#access' => FALSE,
                '#collapsible' => TRUE,
                '#collapsed' => TRUE,
            ),
        );
        $this->attachMethodSettings($form_values, $form, $electoralMethods, 'primary-method', 'primary_settings');
        $this->attachMethodSettings($form_values, $form, $electoralMethods, 'secondary-method', 'secondary_settings');
        $this->attachMethodSettings($form_values, $form, $electoralMethods, 'tertiary-method', 'tertiary_settings');
        return $form;
    }
    
    private function attachMethodSettings(&$values, &$form, &$methods, $methodKey, $methodSettingsKey) {
        if (!empty($values[$methodKey])) {
            $method = $values[$methodKey];
            $obj = $methods[$method]['method'];
            $attached = $obj->buildForm(isset($values[$methodSettingsKey]) ? $values[$methodSettingsKey] : array());
            if (!empty($attached)) {
                $form[$methodSettingsKey] += $attached;
                $form[$methodSettingsKey]['#access'] = TRUE;
            }
        }
    }
    
    private $winners = array();
    private $ballots = array();
    
    private function loadDistrictBallotSets($csv) {
        $this->ballots = array();
        $h = fopen($csv, 'r');
        if (empty($h)) {
            drupal_set_message(t('Error opening CSV file !file', array(
                '!file' => $csv,
            )), 'error');
            return array();
        }
        $keys = array();
        $type = $this->getSetting('ballot-type');
        $last = NULL;
        while (($line = fgetcsv($h)) !== FALSE) {
            if (!empty($line)) {
                if (empty($keys)) {
                    foreach ($line as $index => $label) {
                        $keys[strtolower($label)] = $index;
                    }
                }
                else {
                    if (isset($line[$keys['district']])) {
                        $district = trim($line[$keys['district']]);
                        if (!isset($this->ballots[$district])) {
                            if (!empty($last)) {
                                $this->determineDistrictWinner($this->ballots[$last]);
                                unset($this->ballots[$last]);
                                $last = $district;
                            }
                            $category = isset($keys['category']) ? trim($line[$keys['category']]) : 'All';
                            $seats = 1;
                            $this->ballots[$district] = new DistrictBallotSet($district, array(), $seats, $category);
                        }
                        switch ($type) {
                          case 'rated':
                            $this->ballots[$district]->addBallot($this->parseRatedBallot($line, $keys));
                            break;
                          default:
                          case 'ranked':
                            $this->ballots[$district]->addBallot($this->parseRankedBallot($line, $keys));
                            break;
                        }
                    }
                }
            }
        }
        fclose($h);
    }
    
    private function parseRankedBallot($line, $key) {
        $ranking = array();
        foreach (explode(">", $line[$key['ranking']]) as $rank => $candidates) {
            $candidates = explode('=', $candidates);
            foreach ($candidates as $candidate) {
                $ranking[trim($candidate)] = trim($rank);
            }
        }
        return new RankedBallot($line[$key['votes']], $ranking);
    }
    
    private function parseRatedBallot($line, $key) {
        $pieces = explode(',', $line[$key['ratings']]);
        $ratings = array();
        foreach ($pieces as $candidate) {
            $subpieces = explode('=', $candidate);
            $ratings[trim($subpieces[0])] = trim($subpieces[1]);
        }
        return new RatedBallot($line[$key['votes']], $ratings);
    }
    
    public function runElection() {
        $this->loadDistrictBallotSets($this->getSetting('ballots-csv'));
        $this->winners = array();
        foreach ($this->ballots as $dbs) {
            $this->winners[$dbs->getDistrictName()] = $this->determineDistrictWinner($dbs);
        }
    }
    
    private function determineDistrictWinner(DistrictBallotSet $set) {
        $method = $this->getMethod();
        $method->setVotes($set->getBallots());
        $method->runElection();
        $winners = $method->getWinners();
        $limits = array();
        if (count($winners) === 1) {
            return reset($winners);
        }
        elseif (count($winners) > 1) {
            $limits = $winners;
        }
        $method2 = $this->getMethod('secondary');
        if (!empty($method2)) {
            if (!empty($limits)) {
                $method2->limitPool($limits);
            }
            $method2->setVotes($set->getBallots());
            $method2->runElection();
            $winners = $method2->getWinners();
            if (count($winners) === 1) {
                return reset($winners);
            }
            elseif (count($winners) > 1) {
                $limits = $winners;
            }
        }
        $method3 = $this->getMethod('tertiary');
        if (!empty($method3)) {
            if (!empty($limits)) {
                $method3->limitPool($limits);
            }
            $method3->setVotes($set->getBallots());
            $method3->runElection();
            $winners = $method3->getWinners();
            if (count($winners) === 1) {
                return reset($winners);
            }
        }
        $rand = rand(0, max(array_keys($winners)));
        return $winners[$rand];
    }
    
    /**
     * @return SingleWinnerMethod
     */
    private function getMethod($key = 'primary') {
        $systems = voter_get_methods('single-winner');
        $type = $this->getSetting($key . '-method');
        $system = isset($systems[$type]) ? $systems[$type]['method'] : NULL;
        if ($system instanceof SingleWinnerMethod) {
            $system->reset();
            $system->setSettings($this->getSetting($key . '_settings'));
        }
        return $system;
    }
    
    public function getWinners() {
        return $this->winners;
    }
    
}