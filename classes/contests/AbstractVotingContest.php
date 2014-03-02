<?php

abstract class AbstractVotingContest {
    
    private static $partyColors = array(
        'republican' => '#FF0000',
        'democrat' => '#0000FF',
        'conservative' => '#0000FF',
        'liberal' => '#FF0000',
        'ndp' => '#FF9933',
        'green' => '#00CC00',
        'other' => '#CCCCCC',
        'independent' => '#CCCCCC',
        'bloc' => '#00CCFF'
    );
    
    private static $extraColours = array(
      '#FF0000',
      '#0000FF',
      '#FF9933',
      '#00CC00',
      '#CCCCCC',
      '#00CCFF',
      '#CC0099',
    );
    
    private $settings = array();
    
    public static function getPartyColour($party, array $inUse = array()) {
        $party = strtolower($party);
        if (isset(self::$partyColors[$party])) {
            return self::$partyColors[$party];
        }
        else {
            foreach (self::$extraColours as $colour) {
                if (!in_array($colour, $inUse)) {
                    return $colour;
                }
            }
        }
        return '#000';
    }
    
    public function __construct() {
        
    }
    
    public function buildForm($form_values) {
        return array();
    }
    
    public function setSettings($settings) {
        $this->settings = $settings;
    }
    
    public function getSetting($key) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return NULL;
    }
    
    public abstract function runElection();
    
    public abstract function getWinners();
    
    public function getPartySeats() {
        $winners = $this->getWinners();
        $parties = array();
        foreach ($winners as $winner) {
            $party = $this->getParty($winner);
            if (!isset($parties[$winner])) {
                $parties[$winner] = 0;
            }
            $parties[$winner]++;
        }
        arsort($parties);
        return $parties;
    }
    
    private function getParty($string) {
        $strings = explode("||", $string);
        if (isset($strings[1])) {
            return trim($strings[1]);
        }
        else {
            return 'Independent';
        }
    }
    
}