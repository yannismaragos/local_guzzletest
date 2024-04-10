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
 * Class Config.
 *
 * This class is responsible for managing the configuration of the application.
 * It follows the Singleton design pattern to ensure that only one instance of
 * the configuration exists.
 *
 * @package    local_guzzletest
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guzzletest\httputils;

use GuzzleHttp\Client;
use Exception;

/**
 * Class Config.
 *
 * This class is responsible for managing the configuration of the application.
 * It follows the Singleton design pattern to ensure that only one instance of
 * the configuration exists.
 *
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Config {
    /**
     * The single instance of the class.
     *
     * @var Config|null
     */
    private static $instance = null;

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
     * Configuration settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Config constructor.
     *
     * @param string $baseuri The base URI for the API.
     * @param Client $httpclient The HTTP client used for making API requests.
     */
    private function __construct(string $baseuri, Client $httpclient) {
        $this->baseuri = rtrim($baseuri, '/');
        $this->httpclient = $httpclient;

        $this->settings = [
            // Connection related exceptions.
            'EXCEPTION_CODE_CONNECTION' => 3001,
            // API related exceptions.
            'EXCEPTION_CODE_API' => 4001,
            'EXCEPTION_CODE_BEARER' => 4002,
            'EXCEPTION_CODE_JSON' => 4003,
            'EXCEPTION_CODE_CLIENT' => 4004,
            // Argument related exceptions.
            'EXCEPTION_CODE_URI' => 5001,
            'EXCEPTION_CODE_CREDENTIALS' => 5002,
            'EXCEPTION_CODE_TOKENGENERATOR' => 5003,
            // Settings related exceptions.
            'EXCEPTION_CODE_SETTING' => 6001,
            // Other settings.
            'SETTING_TIMEOUT' => 20,
        ];
    }

    /**
     * Returns an instance of the Config class.
     *
     * @param string $baseuri The base URI for the API.
     * @param Client $client The client object used for making API requests.
     * @return Config An instance of the Config class.
     */
    public static function get_instance($baseuri, $client) {
        if (self::$instance == null) {
            self::$instance = new Config($baseuri, $client);
        }

        return self::$instance;
    }

    /**
     * Retrieves the value of a specific setting.
     *
     * @param string $key The key of the setting to retrieve.
     * @return mixed The value of the setting.
     * @throws Exception If the setting with the specified key is not found.
     */
    public function get_setting($key) {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        throw new Exception("Setting $key not found", $this->settings['EXCEPTION_CODE_SETTING']);
    }

    /**
     * Retrieves the base URI for the API.
     *
     * @return string The base URI for the API.
     */
    public function get_base_uri() {
        return $this->baseuri;
    }

    /**
     * Retrieves the HTTP client.
     *
     * @return Client The HTTP client.
     */
    public function get_http_client() {
        return $this->httpclient;
    }
}
