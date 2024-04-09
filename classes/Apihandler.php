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
use JsonException;

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
     * 3000 series for network-related exceptions
     * 4000 series for API-related exceptions
     */
    const EXCEPTION_CONNECTION = 3001;
    const EXCEPTION_API_REQUEST = 4001;
    const EXCEPTION_BEARER_TOKEN = 4002;
    const EXCEPTION_INVALID_URI = 4003;
    const EXCEPTION_JSON_DECODE = 4004;
    const EXCEPTION_API_RESPONSE = 4005;

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
     * @var string
     */
    private $username;

    /**
     * The password for authentication.
     *
     * @var string
     */
    private $password;

    /**
     * The headers for the API authentication.
     *
     * @var array
     */
    private $authheaders;

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
     * Constructs a new instance of the Apihandler class.
     *
     * @param string $baseuri The base URI for the API.
     */
    public function __construct(string $baseuri) {
        $this->baseuri = rtrim($baseuri, '/');
        $this->authheaders = $this->get_default_auth_headers();
        $this->requestheaders = $this->get_default_request_headers();
        $this->schema = $this->get_default_response_schema();
    }

    /**
     * Retrieves the default authentication headers.
     *
     * @return array The default authentication headers.
     */
    private function get_default_auth_headers(): array {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en',
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
     * Authenticates the user using the provided credentials,  and sets the
     * bearer token in the request headers.
     *
     * @param array $credentials The user's credentials.
     * @return void
     */
    public function authenticate(array $credentials): void {
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        $token = $this->get_bearer_token($credentials['endpoint']);
        $this->requestheaders['authorization'] = "Bearer $token";
    }

    /**
     * Sets the authentication headers for the API request.
     *
     * @param array $headers The authentication headers to be set.
     * @return void
     */
    public function set_authentication_headers(array $headers): void {
        if (!empty($headers)) {
            $this->authheaders = $headers;
        }
    }

    /**
     * Sets the request headers for the API request.
     *
     * @param array $headers The request headers to be set.
     * @return void
     */
    public function set_request_headers(array $headers): void {
        if (!empty($headers)) {
            $authorization = $this->requestheaders['authorization'] ?? null;
            $this->requestheaders = $headers;

            if ($authorization !== null) {
                $this->requestheaders['authorization'] = $authorization;
            }
        }
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
     * @return string The bearer token if authentication is successful.
     * @throws RequestException If there is an error in the API request.
     * @throws Exception If the API request is unsuccessful, or the token is not found.
     * @throws JsonException If JSON decoding is unsuccessful.
     */
    private function get_bearer_token(string $endpoint = ''): string {
        $uri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;

        // Define the JSON payload.
        $body = json_encode([
            "username" => $this->username,
            "password" => $this->password,
            "type" => "1",
        ]);

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();

        // Make up to 3 attempts to connect to the API.
        $attempts = 0;
        while ($attempts < 3) {
            try {
                $response = $client->request('POST', $uri, [
                    'headers' => $this->authheaders,
                    'body' => $body,
                    'timeout' => 20,
                ]);
                break;
            } catch (RequestException $e) {
                $attempts++;
                if ($attempts >= 3) {
                    throw new Exception('Failed to connect to API after 3 attempts.', self::EXCEPTION_CONNECTION);
                }
            }
        }

        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode !== 200) {
            throw new Exception('API request failed with status code: ' . $statuscode, self::EXCEPTION_API_REQUEST);
        }

        $responsedata = json_decode($response->getBody(), true);

        // Check if JSON decoding was successful and there were no errors.
        if ($responsedata === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException('Failed to decode JSON from API response.', self::EXCEPTION_JSON_DECODE);
        }

        // Check if the token is found in the API response.
        if (empty($responsedata['token'])) {
            throw new Exception('Bearer token not found in API response.', self::EXCEPTION_BEARER_TOKEN);
        }

        return $responsedata['token'];
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
     * @return array An array containing the data retrieved from the API
     *               if successful.
     * @throws InvalidArgumentException If there is an invalid argument.
     * @throws RequestException If there is an error in the API request.
     * @throws Exception If the API request is unsuccessful.
     * @throws JsonException If JSON decoding is unsuccessful.
     */
    private function get_data_from_uri(string $uri): array {
        // Validate uri.
        if (empty($uri) || !filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URI.', self::EXCEPTION_INVALID_URI);
        }

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();

        // Make up to 3 attempts to connect to the API.
        $attempts = 0;
        while ($attempts < 3) {
            try {
                $response = $client->request('GET', $uri, [
                    'headers' => $this->requestheaders,
                    'timeout' => 20,
                ]);
                break;
            } catch (RequestException $e) {
                $attempts++;
                if ($attempts >= 3) {
                    throw new Exception('Failed to connect to API.', self::EXCEPTION_CONNECTION);
                }
            }
        }

        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode !== 200) {
            throw new Exception('API request failed with status code: ' . $statuscode, self::EXCEPTION_API_REQUEST);
        }

        $responsedata = json_decode($response->getBody(), true);

        // Check if JSON decoding was successful and there were no errors.
        if ($responsedata === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException('Failed to decode JSON from API response: ' . json_last_error_msg(), self::EXCEPTION_JSON_DECODE);
        }

        return $responsedata;
    }

    /**
     * Retrieves a page of data from the API.
     *
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @param array $params An array of parameters to be included in the API request.
     * @return array The processed results from the API response.
     */
    public function get_page(string $endpoint = '', array $params = []) {
        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $uri = $baseuri . '?' . http_build_query($params, '', '&');
        $response = $this->get_data_from_uri($uri);

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
     * @return array|false An array of results if successful, false otherwise.
     */
    public function get_all_pages(string $endpoint = '', array $params = []) {
        $results = [];
        $baseuri = !empty($endpoint) ? $this->baseuri . '/' . trim($endpoint, '/') : $this->baseuri;
        $page = !empty($params[$this->schema['page_number']]) ? (int) $params[$this->schema['page_number']] : 1;
        $totalpages = null;

        do {
            $params[$this->schema['page_number']] = $page;
            $uri = $baseuri . '?' . http_build_query($params, '', '&');
            $response = $this->get_data_from_uri($uri);

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
