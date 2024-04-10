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

use GuzzleHttp\Client;
use local_guzzletest\httputils\Handler;
use local_guzzletest\httputils\Tokengenerator;
use local_guzzletest\httputils\Config;

// Setup the page.
$context = \core\context\system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/guzzletest/result.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_guzzletest'));
$PAGE->set_heading(get_string('pluginname', 'local_guzzletest'));

echo $OUTPUT->header();

echo 'test-result </br>';
echo '======================================================== </br>';
echo 'request_result </br>';
echo '======================================================== </br>';

// New handler object.
$baseuri = new moodle_url('/local/guzzletest/api');
$client = new Client();
$config = Config::get_instance($baseuri->out(), $client);
$handler = new Handler($config);

// Authenticate (optional).
$credentials = [
    'username' => 'myusername',
    'password' => 'mypassword',
    'endpoint' => 'token.php',
];
$tokengenerator = new Tokengenerator($config);

// Set authentication headers.
$tokengenerator->set_authentication_headers([
    'accept' => 'application/json, text/plain, */*',
    'accept-language' => 'en',
    'connection' => 'keep-alive',
    'content-type' => 'application/json',
    'dnt' => '1',
    'origin' => $baseuri->out(),
    'referer' => $baseuri->out() . '/login/',
    'sec-fetch-dest' => 'empty',
    'sec-fetch-mode' => 'cors',
    'sec-fetch-site' => 'same-origin',
    'sec-gpc' => '1',
    'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
    'x-access-level' => '74',
    'sec-ch-ua' => 'Brave;v="117", Not;A=Brand;v="8", Chromium;v="117"',
    'sec-ch-ua-mobile' => '?0',
    'sec-ch-ua-platform' => 'Linux',
]);

$handler->authenticate($credentials, $tokengenerator);

// Set response schema.
$handler->set_response_schema([
    'page_number' => 'page',
    'page_limit' => 'limit',
    'total_records' => 'total',
    'records' => 'records',
]);

// Set request headers.
$handler->set_request_headers([
    'accept' => '*/*',
    'accept-language' => 'en',
    'connection' => 'keep-alive',
    'dnt' => '1',
    'sec-fetch-dest' => 'empty',
    'sec-fetch-mode' => 'cors',
    'sec-fetch-site' => 'same-origin',
    'sec-gpc' => '1',
    'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
    'sec-ch-ua' => 'Brave;v="117", Not;A=Brand;v="8", Chromium;v="117"',
    'sec-ch-ua-mobile' => '?0',
    'sec-ch-ua-platform' => 'Linux',
]);

// Get page.
$params = [
    'page' => optional_param('page', 1, PARAM_INT),
    'limit' => optional_param('limit', 100, PARAM_INT),
];
$result = $handler->get_page('json.php', $params, []);

// phpcs:ignore
print_object($result);

echo $OUTPUT->footer();
