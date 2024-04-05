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
 * A class for interacting with remote APIs.
 *
 * This class abstracts away the complexities of making HTTP requests and handles
 * authentication and pagination logic.
 *
 * @package    local_guzzletest
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guzzletest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Exception;

/**
 * A class for interacting with remote APIs.
 *
 * This class abstracts away the complexities of making HTTP requests and handles
 * authentication and pagination logic.
 *
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Apihandler {
    /**
     * The base URI.
     *
     * @var string
     */
    private $baseuri;

    /**
     * The HTTP client.
     *
     * @var Client
     */
    private $httpclient;

    /**
     * Dummy bearer token.
     *
     * @var string
     */
    private $dummytoken;

    /**
     * The username for authentication.
     *
     * Storing the username and password directly in the code is not safe and
     * is considered a security risk. It is recommended to use secure methods
     * such as environment variables or configuration files to store sensitive
     * information like usernames and passwords.
     *
     * @var string
     */
    private static $username = 'myuser';

    /**
     * The password for authentication.
     *
     * Storing the username and password directly in the code is not safe and
     * is considered a security risk. It is recommended to use secure methods
     * such as environment variables or configuration files to store sensitive
     * information like usernames and passwords.
     *
     * @var string
     */
    private static $password = 'mypassword';


    /**
     * Class constructor.
     */
    public function __construct(string $baseuri) {
        $this->baseuri = $baseuri;
    }

    /**
     * Set the HTTP client instance.
     *
     * @param Client $httpclient The HTTP client instance.
     *
     * @return void
     */
    public function set_http_client(Client $httpclient): void {
        $this->httpclient = $httpclient;
    }

    /**
     * Set a dummy bearer token.
     *
     * @param string $token The dummy bearer token.
     *
     * @return void
     */
    public function set_dummy_token(string $token): void {
        $this->dummytoken = $token;
    }

    /**
     * Logs a message with mtrace.
     *
     * @param string $message The message to be logged.
     *
     * @return void
     */
    public function log_message(string $message): void {
        mtrace($message);
    }

    /**
     * Get a bearer token from an API by sending an authentication request.
     *
     * This function sends an HTTP POST request to the specified URI to
     * authenticate and retrieve a bearer token. It uses the provided
     * authentication credentials in the request body and expects a successful
     * response with a token field.
     *
     * @return string|false The bearer token if authentication is successful,
     *                      or false on failure.
     * @throws Exception If the API request was not successful or the token is not found.
     * @throws RequestException If there was an error in the API request.
     */
    public function get_bearer_from_api() {
        if ($this->dummytoken) {
            return $this->dummytoken;
        }

        $method = 'POST';
        $uri = $this->baseuri . '/api/ldap/login';
        $headers = [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en;q=0.9',
            'connection' => 'keep-alive',
            'content-type' => 'application/json',
            'dnt' => '1',
            'origin' => $this->baseuri,
            'referer' => $this->baseuri . '/login/',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'sec-gpc' => '1',
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            'x-access-level' => '74',
            'sec-ch-ua' => 'Brave;v="117", Not;A=Brand;v="8", Chromium;v="117"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => 'Linux',
        ];

        // Define the JSON payload.
        $body = json_encode([
            "username" => self::$username,
            "password" => self::$password,
            "type" => "1",
        ]);

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();

        try {
            $response = $client->request($method, $uri, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 20,
            ]);
            $statuscode = $response->getStatusCode();
            $responsedata = json_decode($response->getBody(), true);

            // Check if the API request was successful.
            if ($statuscode === 200) {
                // Check if JSON decoding was successful and there were no errors.
                if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
                    if (!empty($responsedata['token'])) {
                        return $responsedata['token'];
                    }
                }
            }

            // The API request was not successful or token is not found.
            throw new Exception('Failed to obtain bearer token from API.', $statuscode);
        } catch (RequestException | InvalidArgumentException | Exception $e) {
            $errorresponse = [
                'error' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return $errorresponse;
        }
    }

    /**
     * Send an HTTP GET request to a specified URI with authentication headers
     * and process the API response to retrieve data.
     *
     * This function constructs an HTTP GET request to the provided URI with
     * the specified headers, including authentication using a bearer token.
     * It sends the request, checks the response status code, and processes the
     * response data to extract information.
     *
     * @param string $uri The URI to send the API request to.
     * @param string $token The bearer token used for authentication.
     *
     * @return array An array containing the data retrieved from the API
     *               if successful. If an error occurs during the request, the
     *               status code and error message are returned within an array.
     */
    public function get_data_from_api(string $uri, string $token): array {
        // Validate input parameters.
        if (empty($uri) || empty($token)) {
            throw new InvalidArgumentException('Invalid URI or token.');
        }

        $method = 'GET';
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

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();

        try {
            $response = $client->request($method, $uri, [
                'headers' => $headers,
                'timeout' => 20,
            ]);

            $responsedata = json_decode($response->getBody(), true);

            // Check if JSON decoding was successful and there were no errors.
            if ($responsedata !== null && json_last_error() === JSON_ERROR_NONE) {
                $responsedata['status_code'] = $response->getStatusCode();
                return $responsedata;
            }

            // If null or JSON decoding fails.
            throw new Exception('Error decoding JSON data: ' . json_last_error_msg(), $response->getStatusCode());
        } catch (RequestException | InvalidArgumentException | Exception $e) {
            // Handle exceptions and return error message.
            $errorresponse = [
                'error' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return $errorresponse;
        }
    }

    /**
     * Retrieve data from an API endpoint.
     *
     * This function makes API requests to fetch data based on the
     * provided parameters. It retrieves a list of results in paginated
     * form and combines the results into an array.
     *
     * @return array|false An array of objects if successful, or false if
     *                     the bearer token is not found.
     */
    public function get_paginated_data(int $pagelimit = 10) {
        if ($this->dummytoken) {
            $token = $this->dummytoken;
        } else {
            $token = $this->get_bearer_from_api();
        }

        if (!$token) {
            return false;
        }

        $results = [];
        $baseuri = $this->baseuri . '/search';
        $page = 1;
        $totalpages = 0;

        do {
            $uri = "$baseuri?page=$page&limit=" . $pagelimit;
            $response = $this->get_data_from_api($uri, $token);

            if (!empty($response['error']) && !empty($response['message'])) {
                $this->log_message('Error ' . $response['error'] . ': ' . $response['message']);
                break;
            }

            if ($page === 1) {
                $totalpages = isset($response['pages']) ? (int) $response['pages'] : 0;
            }

            if (!empty($response['content'])) {
                $fetchedresults = $response['content'];
            }

            $results = array_merge($results, $fetchedresults);

            // Log response code for each fetched page.
            $this->log_message('Page ' . $page . ': ' . $response['status_code']);

            $page++;
        } while (!empty($fetchedresults) && $page <= $totalpages && !PHPUNIT_TEST);

        // Log total fetched pages.
        $this->log_message(get_string('totalfetchedpages', 'local_guzzletest', ['fetched' => $page - 1, 'total' => $totalpages]));

        // Log total results count that are fetched from API.
        $this->log_message(get_string('totalresults', 'local_guzzletest', count($results)));

        if (!empty($results)) {
            $results = $this->create_objects($results);
        }

        return $results;
    }

    /**
     * Create objects from an array of data.
     *
     * This function takes an array of data and constructs individual
     * objects with specific properties.
     *
     * @param array $resultdata An array of arrays.
     *
     * @return array An array of objects.
     */
    public function create_objects(array $resultdata): array {
        $results = [];

        foreach ($resultdata as $result) {
            $object = new \stdClass();
            // $object->firstname = $result['firstName'] ?? '';
            // $object->lastname = $result['lastName'] ?? '';

            $results[] = $object;
        }

        return $results;
    }
}
