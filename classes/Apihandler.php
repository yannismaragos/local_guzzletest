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
        $this->baseuri = rtrim($baseuri, '/');
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
    public function get_bearer_token() {
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
        } catch (RequestException | Exception $e) {
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
    private function get_data_from_api(string $uri, string $token): array {
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
     * Retrieves the response schema for the API handler.
     *
     * @return array The response schema.
     */
    private function get_response_schema(): array {
        return [
            'page_number' => 'page',
            'page_limit' => 'limit',
            'total_records' => 'total',
            'records' => 'records',
        ];
    }

    /**
     * Retrieves a page of data from the API.
     *
     * @param array $params An array of parameters to be included in the API request.
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @return array The processed results from the API response.
     * @throws Exception If there is an error in the API response.
     */
    public function get_page(array $params = [], string $endpoint = '') {
        if ($this->dummytoken) {
            $token = $this->dummytoken;
        } else {
            $token = $this->get_bearer_token();
        }

        if (!$token) {
            return false;
        }

        // We expect a specific format for the response.
        $schema = $this->get_response_schema();

        $results = [];
        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $uri = $baseuri . '?' . http_build_query($params, '', '&');
        $response = $this->get_data_from_api($uri, $token);

        if (!empty($response['error']) && !empty($response['message'])) {
            throw new Exception('Error ' . $response['error'] . ': ' . $response['message']);
        }

        if (!empty($response[$schema['records']])) {
            $results = $response[$schema['records']];

            return $this->process_results($results);
        }

        return [];
    }

    /**
     * Retrieves all pages of data from the API.
     *
     * This function makes API requests to fetch data based on the
     * provided parameters. It retrieves a list of results in paginated
     * form and combines the results into an array.
     *
     * @param array $params An array of parameters to be sent with the API request.
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @return array|false An array of results if successful, false otherwise.
     * @throws Exception If an error occurs during the API request.
     */
    public function get_all_pages(array $params = [], string $endpoint = '') {
        if ($this->dummytoken) {
            $token = $this->dummytoken;
        } else {
            $token = $this->get_bearer_token();
        }

        if (!$token) {
            return false;
        }

        // We expect a specific format for the response.
        $schema = $this->get_response_schema();

        $results = [];
        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $page = !empty($params[$schema['page_number']]) ? (int) $params[$schema['page_number']] : 1;
        $totalpages = null;

        do {
            $params[$schema['page_number']] = $page;
            $uri = $baseuri . '?' . http_build_query($params, '', '&');
            $response = $this->get_data_from_api($uri, $token);

            if (!empty($response['error']) && !empty($response['message'])) {
                throw new Exception('Error ' . $response['error'] . ': ' . $response['message']);
                break;
            }

            if (empty($totalpages)) {
                if (!empty($response[$schema['total_records']]) && !empty($response[$schema['page_number']])) {
                    $totalpages = 0; //floor((int) $response[$schema['total_records']] / $params[$schema['page_limit']]);
                }
            }

            if (!empty($response[$schema['records']])) {
                $fetchedresults = $response[$schema['records']];

                if (!empty($fetchedresults)) {
                    $results = array_merge($results, $fetchedresults);
                }
            }

            $page++;
        } while (!empty($fetchedresults) && $page <= $totalpages && !PHPUNIT_TEST);

        if (!empty($results)) {
            $results = $this->process_results($results);
        }

        return $results;
    }

    /**
     * Process results.
     *
     * @param array $resultdata An array of results.
     *
     * @return array An array of processed results.
     */
    public function process_results(array $resultdata): array {
        $results = [];

        foreach ($resultdata as $result) {
            $results[] = $result;
        }

        return $results;
    }
}
