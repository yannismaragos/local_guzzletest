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
     * Exception codes.
     *
     * 1000 series for database-related exceptions
     * 2000 series for file I/O exceptions
     * 3000 series for network-related exceptions
     * 4000 series for API-related exceptions
     */
    const EXCEPTION_BEARER_TOKEN = 4001;
    const EXCEPTION_INVALID_PARAMETER = 4002;
    const EXCEPTION_JSON_DECODING_ERROR = 4003;
    const EXCEPTION_API_RESPONSE_ERROR = 4004;

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
     * The username for authentication.
     *
     * Storing the username and password directly in the code is not safe and
     * is considered a security risk. It is recommended to use secure methods
     * such as environment variables or configuration files to store sensitive
     * information like usernames and passwords.
     *
     * @var string
     */
    private static $username = 'myusername';

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
     * The headers for the API token.
     *
     * @var array
     */
    private $tokenheaders;

    /**
     * The headers for the API request.
     *
     * @var array
     */
    private $requestheaders;

    /**
     * The response schema used by the API handler.
     *
     * @var string
     */
    private $schema;

    /**
     * Class constructor.
     */
    public function __construct(string $baseuri) {
        $this->baseuri = rtrim($baseuri, '/');
        $this->tokenheaders = $this->get_default_token_headers();
        $this->requestheaders = $this->get_default_request_headers();
        $this->schema = $this->get_default_response_schema();
    }

    /**
     * Retrieves the default token headers.
     *
     * @return array The default token headers.
     */
    private function get_default_token_headers(): array {
        return [
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
    }

    /**
     * Retrieves the default request headers.
     *
     * @return array The default request headers.
     */
    private function get_default_request_headers(): array {
        return [
            'accept-language' => 'en',
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
    }

    /**
     * Retrieves the default response schema.
     *
     * @return array The default schema.
     */
    private function get_default_response_schema(): array {
        return [
            'page_number' => 'page',
            'page_limit' => 'limit',
            'total_records' => 'total',
            'records' => 'records',
        ];
    }

    /**
     * Sets the response schema.
     *
     * @param array $schema The response schema to set.
     * @return void
     */
    public function set_response_schema(array $schema): void {
        $this->schema = $schema;
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
     * Get a bearer token from an API by sending an authentication request.
     *
     * This function sends an HTTP POST request to the specified URI to
     * authenticate and retrieve a bearer token. It uses the provided
     * authentication credentials in the request body and expects a successful
     * response with a token field.
     *
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @param array $headers (Optional) Additional headers to include in the request.
     * @return string|false The bearer token if authentication is successful,
     *                      or false on failure.
     * @throws Exception If the API request was not successful or the token is not found.
     * @throws RequestException If there was an error in the API request.
     */
    public function get_bearer_token(string $endpoint = '', array $headers = []) {
        $method = 'POST';
        $uri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $this->tokenheaders = empty($headers) ? $this->tokenheaders : $headers;

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
                'headers' => $this->tokenheaders,
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
                        $token = $responsedata['token'];
                        return $token;
                    }
                }
            }

            // The API request was not successful or token is not found.
            throw new Exception('Failed to obtain bearer token from API.', self::EXCEPTION_BEARER_TOKEN);
        } catch (RequestException | Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
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
    private function get_data_from_uri(string $uri, string $token): array {
        // Validate input parameters.
        if (empty($uri) || empty($token)) {
            throw new InvalidArgumentException('Invalid URI or token.', self::EXCEPTION_INVALID_PARAMETER);
        }

        $method = 'GET';

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();

        try {
            $response = $client->request($method, $uri, [
                'headers' => $this->requestheaders,
                'timeout' => 20,
            ]);

            $responsedata = json_decode($response->getBody(), true);

            // Check if JSON decoding was successful and there were no errors.
            if ($responsedata !== null && json_last_error() === JSON_ERROR_NONE) {
                $responsedata['status_code'] = $response->getStatusCode();
                return $responsedata;
            }

            // If null or JSON decoding fails.
            throw new Exception('Error decoding JSON data: ' . json_last_error_msg(), self::EXCEPTION_JSON_DECODING_ERROR);
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
     * Retrieves a page of data from the API.
     *
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @param array $params An array of parameters to be included in the API request.
     * @param array $headers (Optional) Additional headers to include in the request.
     * @return array The processed results from the API response.
     * @throws Exception If there is an error in the API response.
     */
    public function get_page(string $endpoint = '', array $params = [], array $headers = []) {
        // Get token and set headers.
        $this->requestheaders = empty($headers) ? $this->requestheaders : $headers;
        $token = $this->get_bearer_token('token.php');
        $this->requestheaders['authorization'] = "Bearer $token";

        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $uri = $baseuri . '?' . http_build_query($params, '', '&');
        $response = $this->get_data_from_uri($uri, $token);

        if (!empty($response['error']) && !empty($response['message'])) {
            throw new Exception('Error ' . $response['error'] . ': ' . $response['message'], self::EXCEPTION_API_RESPONSE_ERROR);
        }

        if (!empty($response[$this->schema['records']])) {
            return $response[$this->schema['records']];
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
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @param array $params An array of parameters to be sent with the API request.
     * @param array $headers (Optional) Additional headers to include in the request.
     * @return array|false An array of results if successful, false otherwise.
     * @throws Exception If an error occurs during the API request.
     */
    public function get_all_pages(string $endpoint = '', array $params = [], array $headers = []) {
        // Get token and set headers.
        $this->requestheaders = empty($headers) ? $this->requestheaders : $headers;
        $token = $this->get_bearer_token('token.php');
        $this->requestheaders['authorization'] = "Bearer $token";

        $results = [];
        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $page = !empty($params[$this->schema['page_number']]) ? (int) $params[$this->schema['page_number']] : 1;
        $totalpages = null;

        do {
            $params[$this->schema['page_number']] = $page;
            $uri = $baseuri . '?' . http_build_query($params, '', '&');
            $response = $this->get_data_from_uri($uri, $token);

            if (!empty($response['error']) && !empty($response['message'])) {
                throw new Exception('Error ' . $response['error'] . ': ' . $response['message'], self::EXCEPTION_API_RESPONSE_ERROR);
                break;
            }

            if (empty($totalpages)) {
                if (!empty($response[$this->schema['total_records']]) && !empty($response[$this->schema['page_number']])) {
                    $totalpages = 1 + floor((int) $response[$this->schema['total_records']] / $params[$this->schema['page_limit']]);
                }
            }

            if (!empty($response[$this->schema['records']])) {
                $fetchedresults = $response[$this->schema['records']];

                if (!empty($fetchedresults)) {
                    $results = array_merge($results, $fetchedresults);
                }
            }

            $page++;
        } while (!empty($fetchedresults) && $page <= $totalpages && !PHPUNIT_TEST);

        return $results;
    }
}
