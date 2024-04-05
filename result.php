<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test API result.
 *
 * @package    local_guzzletest
 * @copyright  2024 Yannis Maragos <maragos.y@wideservices.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore
require_once('../../config.php');

use local_guzzletest\Apihandler;

$context = \core\context\system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/guzzletest/api/result.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_guzzletest'));
$PAGE->set_heading(get_string('pluginname', 'local_guzzletest'));

echo $OUTPUT->header();

echo 'test-api-result </br>';
echo '======================================================== </br>';
echo 'get_bearer_from_api </br>';
echo '======================================================== </br>';

$baseuri = new moodle_url('/local/guzzletest/api/json.php');
$apihandler = new Apihandler($baseuri);
$dummytoken = 'eyJhbGciOiJIUzI3NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxND' .
    'U2MzQ1Nzg5IiwibmFtZSI6Ik5hbSBTdXBlciIsImlhdCI6MTUxNjIzOTAyMn0.' .
    'Kfl7xwRSJSMeKK2P4fqpwSfJM36POkVySFa_qJssw5c';
$apihandler->set_dummy_token($dummytoken);

$token = $apihandler->get_bearer_from_api();

// phpcs:ignore
print_object($token);

echo '======================================================== </br>';
echo 'request_result </br>';
echo '======================================================== </br>';

if (empty($token)) {
    echo 'Bearer token not found </br>';
} else {
    $params = [
        'page' => optional_param('page', 1, PARAM_INT),
        'limit' => optional_param('limit', 100, PARAM_INT),
    ];

    $result = $apihandler->get_paginated_data($params, '');

    // phpcs:ignore
    print_object($result);
}

echo $OUTPUT->footer();
