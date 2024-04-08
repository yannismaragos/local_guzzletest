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
 * Test API results.
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
$PAGE->set_url(new moodle_url('/local/guzzletest/api/results.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_guzzletest'));
$PAGE->set_heading(get_string('pluginname', 'local_guzzletest'));

echo $OUTPUT->header();

echo 'test-results </br>';
echo '======================================================== </br>';
echo 'get_bearer_token </br>';
echo '======================================================== </br>';

$baseuri = new moodle_url('/local/guzzletest/api');
$apihandler = new Apihandler($baseuri);
$token = $apihandler->get_bearer_token('token.php');

// phpcs:ignore
print_object($token);

echo '======================================================== </br>';
echo 'request_results </br>';
echo '======================================================== </br>';

$params = [
    'page' => optional_param('page', 1, PARAM_INT),
    'limit' => optional_param('limit', 100, PARAM_INT),
];

$result = $apihandler->get_all_pages($params, 'json.php');

// phpcs:ignore
print_object($result);

echo $OUTPUT->footer();
