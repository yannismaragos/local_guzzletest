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
 * Class Tokengenerator
 *
 * This class is responsible for generating tokens. It uses the Config class
 * for configuration settings.
 *
 * @package    local_guzzletest
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guzzletest\httputils;

use GuzzleHttp\Exception\RequestException;
use local_guzzletest\httputils\Config;
use InvalidArgumentException;
use Exception;
use JsonException;

/**
 * Class Tokengenerator
 *
 * This class is responsible for generating tokens. It uses the Config class
 * for configuration settings.
 *
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Tokengenerator {
    /**
     * The configuration object.
     *
     * @var Config
     */
    private $config;

    /**
     * The headers for the API authentication.
     *
     * @var array
     */
    private $authheaders;

    /**
     * Constructor for the Tokengenerator class.
     *
     * @param Config $config The configuration object.
     */
    public function __construct(Config $config) {
        $this->config = $config;
        $this->authheaders = $this->get_default_auth_headers();
    }

    /**
     * Retrieves the default authentication headers.
     *
     * @return array The default authentication headers.
     */
    public function get_default_auth_headers(): array {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en',
            'connection' => 'keep-alive',
            'content-type' => 'application/json',
            'dnt' => '1',
            'origin' => $this->config->get_base_uri(),
            'referer' => $this->config->get_base_uri() . '/login/',
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
     * Get a bearer token from an API by sending an authentication request.
     *
     * This function sends an HTTP POST request to the specified URI to
     * authenticate and retrieve a bearer token. It uses the provided
     * authentication credentials in the request body and expects a successful
     * response with a token field.
     *
     * @param string $username The username for authentication.
     * @param string $password The password for authentication.
     * @param string $endpoint (optional) The endpoint to be appended to the base URI.
     * @return string The bearer token if authentication is successful.
     * @throws RequestException If there is an error in the API request.
     * @throws Exception If the API request is unsuccessful, or the token is not found.
     * @throws JsonException If JSON decoding is unsuccessful.
     */
    public function get_bearer_token(string $username, string $password, string $endpoint = ''): string {
        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException(
                'Missing credentials in get_bearer_token method.',
                $this->config->get_setting('EXCEPTION_CODE_CREDENTIALS')
            );
        }

        $uri = !empty($endpoint)
            ? $this->config->get_base_uri() . '/' . trim($endpoint, '/')
            : $this->config->get_base_uri();

        // Define the JSON payload.
        $body = json_encode([
            "username" => $username,
            "password" => $password,
            "type" => "1",
        ]);

        $client = $this->config->get_http_client();

        if ($client === null) {
            throw new Exception('HTTP client is null', $this->config->get_setting('EXCEPTION_CODE_CLIENT'));
        }

        // Retry attempt to connect to the API.
        $attempts = 0;
        $retrylimit = $this->config->get_setting('SETTING_RETRY_LIMIT');

        while ($attempts < $retrylimit) {
            try {
                $response = $client->request('POST', $uri, [
                    'headers' => $this->authheaders,
                    'body' => $body,
                    'timeout' => $this->config->get_setting('SETTING_TIMEOUT'),
                ]);
                break;
            } catch (RequestException $e) {
                $attempts++;
                if ($attempts >= $retrylimit) {
                    throw new Exception(
                        'Failed to connect to API.',
                        $this->config->get_setting('EXCEPTION_CODE_CONNECTION')
                    );
                }
            }
        }

        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode !== 200) {
            throw new Exception(
                'API request failed with status code: ' . $statuscode,
                $this->config->get_setting('EXCEPTION_CODE_API')
            );
        }

        $responsedata = json_decode($response->getBody(), true);

        // Check if JSON decoding was successful and there were no errors.
        if ($responsedata === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(
                'Failed to decode JSON from API response.',
                $this->config->get_setting('EXCEPTION_CODE_JSON')
            );
        }

        // Check if the token is found in the API response.
        if (empty($responsedata['token'])) {
            throw new Exception(
                'Bearer token not found in API response.',
                $this->config->get_setting('EXCEPTION_CODE_BEARER')
            );
        }

        return $responsedata['token'];
    }
}
