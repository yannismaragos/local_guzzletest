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
 * Class for data operations.
 *
 * A class for handling data operations, including interaction with an API,
 * user management, and database table operations.
 *
 * @package    local_guzzletest
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_guzzletest;

defined('MOODLE_INTERNAL') || die();

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class for data operations.
 *
 * A class for handling data operations, including interaction with an API,
 * user management, and database table operations.
 *
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /**
     * The base URI.
     *
     * @var string
     */
    public static $baseuri = 'https://aen.gov.gr';

    /**
     * The username used for HTTP login authentication.
     *
     * @var string
     */
    private static $httploginusername = 'wsuseraen';

    /**
     * The password used for HTTP login authentication.
     *
     * @var string
     */
    private static $httploginpassword  = 'ws_user_aen';

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\Client
     */
    private $httpclient;

    /**
     * The maximum number of users per page.
     *
     * @var int
     */
    public static $pagelimit = 20;

    /**
     * The shortname for the 'am' profile field.
     *
     * @var string
     */
    public static $amshortname = 'am';

    /**
     * Indicates the current state of mtrace logging.
     *
     * @var bool
     */
    private $mtraceenabled = true;

    /**
     * Class constructor.
     */
    public function __construct() {
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
     * Set the HTTP client instance.
     *
     * @param \GuzzleHttp\Client $httpclient The HTTP client instance.
     *
     * @return void
     */
    public function set_http_client(\GuzzleHttp\Client $httpclient): void {
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
     * @return string|false The bearer token if authentication is successful,
     *                      or false on failure.
     */
    public function get_bearer_from_api() {
        $method = 'POST';
        $uri = self::$baseuri . '/api/ldap/login';
        $headers = [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en;q=0.9',
            'connection' => 'keep-alive',
            'content-type' => 'application/json',
            'dnt' => '1',
            'origin' => self::$baseuri,
            'referer' => self::$baseuri . '/login/',
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
            "username" => self::$httploginusername,
            "password" => self::$httploginpassword,
            "type" => "1",
        ]);

        // Use the provided client or create a new client.
        $client = $this->httpclient ?? new Client();
        $response = $client->request($method, $uri, [
            'headers' => $headers,
            'body' => $body,
        ]);
        $statuscode = $response->getStatusCode();

        // Check if the API request was successful.
        if ($statuscode === 200) {
            $responsedata = json_decode($response->getBody(), true);

            // Check if JSON decoding was successful and there were no errors.
            if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
                if (!empty($responsedata['token'])) {
                    return $responsedata['token'];
                }
            }
        }

        return false;
    }

    /**
     * Retrieve a user's profile field value based on a shortname.
     *
     * This function retrieves the value of a user's profile field with the
     * specified shortname.
     *
     * @param string $shortname The shortname of the profile field.
     *
     * @return string The value of the specified profile field, or an empty
     *                string if not found.
     */
    public function get_profile_field_value(string $shortname): string {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user = clone ($USER);
        profile_load_data($user);
        $amfieldname = 'profile_field_' . $shortname;

        if (isset($user->$amfieldname)) {
            return (string) $user->$amfieldname;
        }

        return '';
    }

    /**
     * Create user objects from an array of user data.
     *
     * This function takes an array of user data and constructs individual
     * user objects with specific properties. It also includes optional custom
     * profile fields if they are present in the user data array.
     * If there is no 'email' or 'am' values, the user is skipped.
     *
     * @param array $userdata An array of user arrays.
     *
     * @return array An array of user objects.
     */
    public function create_user_objects(array $userdata): array {
        $users = [];
        $userswithoutamcount = 0;
        $userswithoutam = [];
        $userswithoutemailcount = 0;
        $userswithoutemail = [];
        $userswithinvalidemailcount = 0;
        $userswithinvalidemail = [];

        foreach ($userdata as $user) {
            $userobject = new \stdClass();
            $userobject->firstname = $user['firstName'] ?? '';
            $userobject->lastname = $user['lastName'] ?? '';

            // Custom field 'am'.
            if (!empty($user['am'])) {
                $userobject->profile_field_am = $user['am'];
            } else {
                // Skip this user if there is no 'am' value.
                $userswithoutamcount++;
                $userswithoutam[] = $user['id'];
                continue;
            }

            if (!empty($user['email'])) {
                $email = 'uat01_' . strtolower($user['email']);

                // Check for valid email to be used as username.
                if ($email !== \core_user::clean_field($email, 'username')) {
                    // Skip this user if there is an invalid email.
                    $userswithinvalidemailcount++;
                    $userswithinvalidemail[] = $user['id'];
                    continue;
                } else {
                    $userobject->email = $email;
                }
            } else {
                // Skip this user if there is no email.
                $userswithoutemailcount++;
                $userswithoutemail[] = $user['id'];
                continue;
            }

            // Profile custom fields.
            $userobject->profile_field_afm = $user['afm'] ?? '';
            $userobject->profile_field_academy = $user['academy'] ?? '';
            $userobject->profile_field_school = $user['school'] ?? '';
            $userobject->profile_field_eduyear = $user['eduYear'] ?? '';
            $userobject->profile_field_eduperiod = $user['eduPeriod'] ?? '';

            $users[] = $userobject;
        }

        // Log users count without 'am'.
        if ($userswithoutamcount) {
            $this->log_message(get_string('userswithoutam', 'local_guzzletest', $userswithoutamcount));

            foreach ($userswithoutam as $id) {
                $this->log_message('- [id] => ' . $id);
            }
        }

        // Log users count without email.
        if ($userswithoutemailcount) {
            $this->log_message(get_string('userswithoutemail', 'local_guzzletest', $userswithoutemailcount));

            foreach ($userswithoutemail as $id) {
                $this->log_message('- [id] => ' . $id);
            }
        }

        // Log users count with invalid email.
        if ($userswithinvalidemailcount) {
            $this->log_message(get_string('userswithinvalidemail', 'local_guzzletest', $userswithinvalidemailcount));

            foreach ($userswithinvalidemail as $id) {
                $this->log_message('- [id] => ' . $id);
            }
        }

        return $users;
    }

    /**
     * Send an HTTP GET request to a specified URI with authentication headers
     * and process the API response to retrieve user data.
     *
     * This function constructs an HTTP GET request to the provided URI with
     * the specified headers, including authentication using a bearer token.
     * It sends the request, checks the response status code, and processes the
     * response data to extract information.
     *
     * @param string $uri The URI to send the API request to.
     * @param string $token The bearer token used for authentication.
     *
     * @return array An array containing the user data retrieved from the API
     *               if successful. If an error occurs during the request, the
     *               status code and error message are returned within an array.
     */
    public function request_users(string $uri, string $token): array {
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
            ]);

            $responsedata = json_decode($response->getBody(), true);

            // Check if JSON decoding was successful and there were no errors.
            if ($responsedata !== null && json_last_error() == JSON_ERROR_NONE) {
                $responsedata['status_code'] = $response->getStatusCode();
                return $responsedata;
            }

            // If null or JSON decoding fails.
            $errorresponse = [
                'error' => $response->getStatusCode(),
                'message' => ($responsedata === null) ? 'Received null response.' : 'Error decoding JSON data.',
            ];

            return $errorresponse;
        } catch (RequestException $e) {
            // Handle exceptions and return error message.
            $errorresponse = [
                'error' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return $errorresponse;
        }

        return [];
    }

    /**
     * Retrieve user data from an API endpoint.
     *
     * This function makes API requests to fetch user data based on the
     * provided parameters. If a specific user ID is provided, it fetches a
     * single user's data. Otherwise, it retrieves a list of users in paginated
     * form and combines the results into an array.
     *
     * @param int|null $userid Optional The ID of the event user. If provided,
     *                 a specific user will be fetched, otherwise, all users
     *                 will be fetched.
     * @param bool $istest Optional Whether the function is called from a test.
     *
     * @return array|false An array of user objects if successful, or false if
     *                     the bearer token is not found.
     */
    public function get_users_from_api(?int $userid = null, bool $istest = false) {
        $token = $this->get_bearer_from_api();

        if (!$token) {
            return false;
        }

        $users = [];
        $baseuri = self::$baseuri . '/api/ws/students/list';

        if ($userid) {
            $am = $this->get_profile_field_value(self::$amshortname);

            if (!$am || $am === '') {
                return [];
            }

            $uri = "$baseuri?am=$am";
            $response = $this->request_users($uri, $token);

            if (!empty($response['error']) && !empty($response['message'])) {
                $this->log_message('Error ' . $response['error'] . ': ' . $response['message']);
                return [];
            }

            if (!empty($response['content'])) {
                $users = $response['content'];
            }
        } else {
            $page = 1;
            $totalpages = 0;

            do {
                $uri = "$baseuri?page=$page&limit=" . self::$pagelimit;
                $response = $this->request_users($uri, $token);

                if (!empty($response['error']) && !empty($response['message'])) {
                    $this->log_message('Error ' . $response['error'] . ': ' . $response['message']);
                    break;
                }

                if ($page === 1) {
                    $totalpages = isset($response['pages']) ? (int) $response['pages'] : 0;
                }

                if (!empty($response['content'])) {
                    $fetchedusers = $response['content'];
                }

                $users = array_merge($users, $fetchedusers);

                // Log response code for each fetched page.
                $this->log_message('Page ' . $page . ': ' . $response['status_code']);

                $page++;
            } while (!empty($fetchedusers) && $page <= $totalpages && !$istest);

            // Log total fetched pages.
            $this->log_message(get_string('totalfetchedpages', 'local_guzzletest', ['fetched' => $page - 1, 'total' => $totalpages]));

            // Log total users count that are fetched from API.
            $this->log_message(get_string('totalusers', 'local_guzzletest', count($users)));
        }

        if (!empty($users)) {
            $users = $this->create_user_objects($users);
        }

        return $users;
    }

    /**
     * Update a user with the provided data.
     *
     * @param \stdClass $user An object containing user data.
     *
     * @return void
     */
    public function update_user(\stdClass $user): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Update user.
        user_update_user($user, false, true);

        // Update user profile fields.
        profile_save_data($user);
    }

    /**
     * Log the user update to the table #_local_guzzletest_userlog.
     *
     * @param int $userid The id of the user.
     * @param int $afm The afm key of the user.
     * @param int $am The am key of the user.
     *
     * @return int|false The id of the newly created record, or false on
     *                   failure.
     */
    public function log_user_update(int $userid, int $afm, int $am) {
        global $DB;

        $time = new \DateTime('now');
        $record = new \stdClass();
        $record->userid = $userid;
        $record->afm = $afm;
        $record->am = $am;
        $record->timecreated = $time->getTimestamp();

        return $DB->insert_record('local_guzzletest_userlog', $record);
    }

    /**
     * Create a user in the database with the provided data.
     *
     * @param \stdClass $user An object containing user data.
     *
     * @return bool True if the user is created successfully, false if an
     *              exception occurs.
     */
    public function create_user(\stdClass $user): bool {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user->username = strtolower($user->email);
        $user->firstnamephonetic = '';
        $user->lastnamephonetic = '';
        $user->middlename = '';
        $user->alternatename = '';

        // Create user.
        try {
            if (!empty($user->profile_field_afm)) {
                $updatepassword = false;
                $user->auth = 'oauth2';
            } else {
                $updatepassword = true;
                $user->password = 'A12345$a';
                $user->auth = 'manual';
            }

            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $userid = user_create_user($user, $updatepassword, true);

            // Return if cannot create user.
            if (!$userid) {
                return false;
            }

            $user->id = $userid;

            // Create user profile fields.
            profile_save_data($user);

            if ($user->profile_field_afm == '' || !$user->profile_field_afm) {
                // Set user preference for enforcing password change on login.
                set_user_preference('auth_forcepasswordchange', 1, $user->id);

                // Test uat-01: override user email with a test email.
                $user->email = 'pulsarbytesnet@gmail.com';

                // Set new password and send email to user.
                if (setnew_password_and_mail($user)) {
                    $this->log_message(get_string('emailuser', 'local_guzzletest', $user->email));
                }
            }
        } catch (Exception $e) {
            $this->log_message($e->getMessage());
            return false;
        }

        return true;
    }
}
