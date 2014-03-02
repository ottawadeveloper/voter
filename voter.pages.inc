<?php

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

function voter_form($form, &$form_state) {
    $systems = array();
    foreach (voter_get_contests() as $key => $contest) {
        $systems[$key] = t($contest['title']);
    }
    $form['voting-method'] = array(
        '#title' => t('Contest Type'),
        '#type' => 'select',
        '#options' => $systems,
        '#required' => TRUE,
        '#ajax' => array(
            'callback' => 'voter_ajax_full_form',
            'wrapper' => 'contest-settings',
        ),
    );
    
    $selectedMethod = isset($form_state['values']['voting-method']) ? $form_state['values']['voting-method'] : NULL;
    
    $form_values = isset($form_state['values']['contest_settings']) ? $form_state['values']['contest_settings'] : array();
    
    $form += array(
        '#prefix' => '<div id="contest-settings">',
        '#suffix' => '</div>',
    );
    
    $form['contest_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('Contest Settings'),
        '#collapsible' => TRUE,
        '#tree' => TRUE,
        '#access' => FALSE,
    );
    $form['results'] = array(
        '#weight' => 50,
        '#access' => FALSE,
        '#prefix' => '<h2>' . t('Results') . '</h2>',
    );
    
    if (!empty($selectedMethod)) {
        $contestEntry = voter_get_contests($selectedMethod);
        $contest = $contestEntry['contest'];
        $form['contest_settings'] += $contest->buildForm($form_values);
        $form['contest_settings']['#access'] = TRUE;
        if ($form_state['triggering_element']['#type'] === 'submit') {
            $contest->setSettings($form_values);
            $contest->runElection();
            $partySeats = $contest->getPartySeats();
            $form['#attached'] = array(
                'css' => array(
                    drupal_get_path('module', 'voter') . '/css/voter.css',
                ),
            );
            $html = '';
            $breakdown = '';
            $total = array_sum($partySeats);
            $best = '';
            foreach ($partySeats as $party => $seats) {
                $breakdown .= '<li><strong>' . $party . '</strong> ' . $seats . '</li>';
                $html .= '<span class="voter-party-seats" style="width: '.(($seats / $total) * 100).'%; background-color: '.AbstractVotingContest::getPartyColour($party).'; color: #FFF;" title="' . $party . ' ('.$seats.')"></span>';
                if (empty($best)) {
                    $best = $party . ' ' . ($seats >= (floor($total / 2) + 1) ? t('Majority') : t('Minority'));
                }
            }
            $form['results']['#access'] = TRUE;
            $form['results']['party-seats-graphic'] = array(
                '#type' => 'markup',
                '#markup' => $html,
                '#prefix' => '<div class="voter-seats-graphic">',
                '#suffix' => '</div><p style="text-align: right; font-weight: bold;">' . $best . '</p>',
            );
            $form['results']['party-seats-breakdown'] = array(
                '#type' => 'markup',
                '#prefix' => '<h3>Seat Breakdown</h2><ol class="voter-seats-breakdown">',
                '#suffix' => '</ol>',
                '#markup' => $breakdown,
            );
            $form['contest_settings']['#collapsed'] = TRUE;
            //$form['test'] = array('#markup' => var_export($partySeats, TRUE), '#prefix' => '<pre>', '#suffix' => '</pre>');
        }
    }
    
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Run Election'),
    );
    return $form;
}

function voter_form_submit($form, &$form_state) {
    $form_state['rebuild'] = TRUE;
}
