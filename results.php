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

use local_guzzletest\Apihandler as api;
use GuzzleHttp\Client;

$context = \core\context\system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/guzzletest/api/results.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_guzzletest'));
$PAGE->set_heading(get_string('pluginname', 'local_guzzletest'));

echo $OUTPUT->header();

echo 'test-api-results </br>';
echo '======================================================== </br>';
echo 'get_bearer_from_api </br>';
echo '======================================================== </br>';

$baseuri = 'http://universities.hipolabs.com';
$api = new api($baseuri);
$dummytoken = 'eyJhbGciOiJIUzI3NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxND' .
    'U2MzQ1Nzg5IiwibmFtZSI6Ik5hbSBTdXBlciIsImlhdCI6MTUxNjIzOTAyMn0.' .
    'Kfl7xwRSJSMeKK2P4fqpwSfJM36POkVySFa_qJssw5c';
$api->set_dummy_token($dummytoken);

$token = $api->get_bearer_from_api();

// phpcs:ignore
print_object($token);

echo '======================================================== </br>';
echo 'request_results </br>';
echo 'http://universities.hipolabs.com/search?country=Greece </br>';
echo '======================================================== </br>';

if (empty($token)) {
    echo 'Bearer token not found </br>';
} else {
    die;

    $page = isset($_GET['page']) ? $_GET['page'] : -1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
    $method = 'GET';
    $uri = "https://aen.gov.gr/api/ws/students/list?page=$page&limit=$limit";
    $headers = [
        'accept-language' => 'en',
        'authorization' => "Bearer $token",
        'connection' => 'keep-alive',
        'dnt' => '1',
        'sec-fetch-dest' => 'empty',
        'sec-fetch-mode' => 'cors',
        'sec-fetch-site' => 'same-origin',
        'sec-gpc' => '1',
        'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'accept' => '*/*',
        'sec-ch-ua' => 'Brave;v="117", Not;A=Brand;v="8", Chromium;v="117"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => 'Linux',
    ];

    if ($page >= 0) {
        // Use the provided client or create a new client.
        $client = new Client();
        $response = $client->request($method, $uri, [
            'headers' => $headers,
        ]);
        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode === 200) {
            $responsedata = json_decode($response->getBody(), true);

            // Check if JSON decoding was successful and there were no errors.
            if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
                echo '<pre>';
                // phpcs:ignore
                print_object($responsedata);
                echo '</pre>';
            }
        } else {
            echo "Statuscode: $statuscode </br>";
        }
    } else {
        $page = 1;

        do {
            $uri = "https://aen.gov.gr/api/ws/students/list?page=$page&limit=$limit";
            $page++;

            // Use the provided client or create a new client.
            $client = new Client();
            $response = $client->request($method, $uri, [
                'headers' => $headers,
            ]);
            $statuscode = $response->getStatusCode();

            // Check if the API request was successful.
            if ($statuscode === 200) {
                $responsedata = json_decode($response->getBody(), true);

                // Check if JSON decoding was successful and there were no errors.
                if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
                    echo '<pre>';
                    // phpcs:ignore
                    print_object($responsedata);
                    echo '</pre>';
                }
            } else {
                echo "Statuscode: $statuscode </br>";
            }
        } while (!empty($responsedata['content']) && $statuscode === 200);
    }
}

echo $OUTPUT->footer();
