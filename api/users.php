<?php
// phpcs:disable
require_once('../../../../config.php');

use GuzzleHttp\Client;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/guzzletest/api/users.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_guzzletest'));
$PAGE->set_heading(get_string('pluginname', 'local_guzzletest'));

echo $OUTPUT->header();

echo 'test-api-users </br>';
echo '======================================================== </br>';
echo 'get_bearer_from_api </br>';
echo '======================================================== </br>';

$method = 'POST';
$uri = 'https://aen.gov.gr/api/ldap/login';
$headers = [
    'accept' => 'application/json, text/plain, */*',
    'accept-language' => 'en;q=0.9',
    'connection' => 'keep-alive',
    'content-type' => 'application/json',
    'dnt' => '1',
    'origin' => 'https://aen.gov.gr',
    'referer' => 'https://aen.gov.gr/login/',
    'sec-fetch-dest' => 'empty',
    'sec-fetch-mode' => 'cors',
    'sec-fetch-site' => 'same-origin',
    'sec-gpc' => '1',
    'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
    'x-access-level' => '74',
    'sec-ch-ua' => 'Brave;v="117", Not;A=Brand;v="8", Chromium;v="117"',
    'sec-ch-ua-mobile' => '?0',
    'sec-ch-ua-platform' => 'Linux'
];

// Define the JSON payload.
$body = json_encode([
    "username" => 'wsuseraen',
    "password" => 'ws_user_aen',
    "type" => "1",
]);

$client = new Client();
$response = $client->request($method, $uri, [
    'headers' => $headers,
    'body' => $body,
]);
$statuscode = $response->getStatusCode();
$token = '';

// Check if the API request was successful.
if ($statuscode === 200) {
    $responsedata = json_decode($response->getBody(), true);

    // Check if JSON decoding was successful and there were no errors.
    if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
        if (!empty($responsedata['token'])) {
            $token = $responsedata['token'];
            echo '<pre>';
            print_r($token);
            echo '</pre>';
        }
    }
} else {
    echo "Statuscode: $statuscode </br>";
}

echo '======================================================== </br>';
echo 'request_users </br>';
echo '======================================================== </br>';

if (empty($token)) {
    echo 'Bearer token not found </br>';
} else {
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
                print_r($responsedata);
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
                    print_r($responsedata);
                    echo '</pre>';
                }
            } else {
                echo "Statuscode: $statuscode </br>";
            }
        } while (!empty($responsedata['content']) && $statuscode === 200);
    }
}

echo $OUTPUT->footer();
