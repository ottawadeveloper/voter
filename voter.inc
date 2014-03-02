<?php

function _voter_find_methods() {
    return module_invoke_all('voting_methods');
}

function _voter_find_contests() {
    return module_invoke_all('voting_contests');
}