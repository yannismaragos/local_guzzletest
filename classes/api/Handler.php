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

namespace local_guzzletest\api;

use local_guzzletest\api\Config;
use local_guzzletest\api\Tokengenerator;
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
class Handler {
    /**
     * The configuration object.
     *
     * @var Config
     */
    private $config;

    /**
     * The headers for the API request.
     *
     * @var array
     */
    private $requestheaders;

    /**
     * The response schema used by the API handler.
     *
     * @var array
     */
    private $schema;

    /**
     * Constructor for the Handler class.
     *
     * @param Config $config The configuration object.
     */
    public function __construct(Config $config) {
        $this->config = $config;
        $this->requestheaders = $this->get_default_request_headers();
        $this->schema = $this->get_default_response_schema();
    }

    /**
     * Retrieves the default request headers.
     *
     * @return array The default request headers.
     */
    public function get_default_request_headers(): array {
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
    public function get_default_response_schema(): array {
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
     * Retrieves the response schema.
     *
     * @return array The response schema.
     */
    public function get_response_schema(): array {
        return $this->schema;
    }

    /**
     * Authenticates the user using the provided credentials, and sets the
     * bearer token in the request headers.
     *
     * @param array $credentials The user's credentials.
     * @param Tokengenerator $tokengenerator The token generator object.
     * @return void
     * @throws InvalidArgumentException If credentials or tokengenerator are missing.
     */
    public function authenticate(array $credentials, Tokengenerator $tokengenerator): void {
        if (!isset($credentials['username'], $credentials['password'])) {
            throw new InvalidArgumentException(
                'Missing credentials in authenticate method.',
                $this->config->get_setting('EXCEPTION_MISSING_CREDENTIALS')
            );
        }

        if (!isset($tokengenerator)) {
            throw new InvalidArgumentException(
                'Missing tokengenerator.',
                $this->config->get_setting('EXCEPTION_MISSING_TOKENGENERATOR')
            );
        }

        $token = $tokengenerator->get_bearer_token(
            $credentials['username'],
            $credentials['password'],
            $credentials['endpoint'] ?? ''
        );
        $this->requestheaders['authorization'] = "Bearer $token";
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
     * Send an HTTP GET request to a specified URI with authentication headers
     * and process the API response to retrieve data.
     *
     * This function constructs an HTTP GET request to the provided URI with
     * the specified headers, including authentication using a bearer token.
     * It sends the request, checks the response status code, and processes the
     * response data to extract information.
     *
     * @param string $uri The URI to send the API request to.
     * @return array An array containing the data retrieved from the API if successful.
     * @throws InvalidArgumentException If there is an invalid argument.
     * @throws RequestException If there is an error in the API request.
     * @throws Exception If the API request is unsuccessful.
     * @throws JsonException If JSON decoding is unsuccessful.
     */
    private function get_data_from_uri(string $uri): array {
        // Validate uri.
        if (empty($uri) || !filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URI.', $this->config->get_setting('EXCEPTION_INVALID_URI'));
        }

        $client = $this->config->get_http_client();

        // Make up to 3 attempts to connect to the API.
        $attempts = 0;

        while ($attempts < 3) {
            try {
                $response = $client->request('GET', $uri, [
                    'headers' => $this->requestheaders,
                    'timeout' => $this->config->get_setting('TIMEOUT'),
                ]);
                break;
            } catch (RequestException $e) {
                $attempts++;
                if ($attempts >= 3) {
                    throw new Exception(
                        'Failed to connect to API after 3 attempts.',
                        $this->config->get_setting('EXCEPTION_CONNECTION')
                    );
                }
            }
        }

        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode !== 200) {
            throw new Exception(
                'API request failed with status code: ' . $statuscode,
                $this->config->get_setting('EXCEPTION_API_REQUEST')
            );
        }

        $responsedata = json_decode($response->getBody(), true);

        // Check if JSON decoding was successful and there were no errors.
        if ($responsedata === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(
                'Failed to decode JSON from API response: ' . json_last_error_msg(),
                $this->config->get_setting('EXCEPTION_JSON_DECODE')
            );
        }

        return $responsedata;
    }

    /**
     * Retrieves a page of data from the API.
     *
     * @param string $endpoint The endpoint to be appended to the base URI.
     * @param array $params An array of parameters to be included in the API request.
     * @return array The results from the API response.
     */
    public function get_page(string $endpoint = '', array $params = []): array {
        $baseuri = !empty($endpoint)
            ? $this->config->get_base_uri() . '/' . trim($endpoint, '/')
            : $this->config->get_base_uri();
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
     * @return array An array of results.
     */
    public function get_all_pages(string $endpoint = '', array $params = []): array {
        $results = [];
        $baseuri = !empty($endpoint)
            ? $this->config->get_base_uri() . '/' . trim($endpoint, '/')
            : $this->config->get_base_uri();
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
