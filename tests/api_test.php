<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_guzzletest;

defined('MOODLE_INTERNAL') || die();

use local_guzzletest\api;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests for the api class.
 *
 * @package    local_guzzletest
 * @category   test
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_guzzletest\api
 */
class api_test extends \advanced_testcase {
    /**
     * Setup tasks for all tests.
     *
     * @return void
     */
    protected function setUp(): void {
    }

    /**
     * Test the 'get_bearer_from_api' function.
     *
     * This test method verifies the behavior of the 'get_bearer_from_api'
     * function by making a simulated API request using a mock HTTP client.
     * It ensures that the function correctly processes the API response and
     * returns the expected token.
     *
     * Test Steps:
     * 1. Define the expected API response token for the mock.
     * 2. Mock the HTTP client to simulate the API response with the expected token.
     * 3. Replace the actual HTTP client with the mock for testing.
     * 4. Call the 'get_bearer_from_api' function being tested.
     * 5. Assert that the result matches the expected token.
     *
     * @covers \local_guzzletest\api::get_bearer_from_api
     *
     * @return void
     */
    public function test_get_bearer_from_api_success(): void {
        // Define the expected API response token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Mock the HTTP client to simulate the API response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () use ($token) {
            // Simulate a successful API response.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['token' => $token])
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api();
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_bearer_from_api();

        // Assert that the result matches the expected token.
        $this->assertSame($token, $result);
    }

    /**
     * Test the `get_bearer_from_api` function with various API response cases.
     *
     * This test method verifies the behavior of the 'get_bearer_from_api'
     * function by making a simulated API request using a mock HTTP client.
     * It ensures that the function correctly processes the API response when
     * the token is an empty string or false, or if the JSON data is invalid.
     *
     * @param string $data The JSON-encoded data representing the API response
     *               or a string representing invalid JSON format.
     * @covers \local_guzzletest\api::get_bearer_from_api
     * @dataProvider get_bearer_from_api_provider
     *
     * @return void
     */
    public function test_get_bearer_from_api_empty_false_invalid(string $data): void {
        // Mock the HTTP client to simulate the API response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () use ($data) {
            // Simulate a successful API response.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                $data
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api();
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_bearer_from_api();

        // Assert that the result is false.
        $this->assertSame(false, $result);
    }

    /**
     * Data provider for {@see test_get_bearer_from_api_empty_false_invalid}
     *
     * @return array
     */
    public static function get_bearer_from_api_provider(): array {
        return [
            'Empty string' => ['data' => json_encode(['token' => ''])],
            'False' => ['data' => json_encode(['token' => false])],
            'Invalid' => ['data' => 'invalid_json_data'],
        ];
    }

    /**
     * Test the 'get_profile_field_value' function when the specified profile
     * field value exists or is an empty string.
     *
     * This test method verifies that the 'get_profile_field_value' function
     * correctly retrieves the value of an existing custom profile field for
     * the user.
     *
     * It performs the following steps:
     * 1. Sets up the necessary user context for testing.
     * 2. Sets a custom profile field with a specified shortname and value for
     *    the user.
     * 3. Calls the 'get_profile_field_value' function with the shortname of
     *    the custom profile field.
     * 4. Asserts that the function returns the expected value, or an empty
     *    string.
     *
     * @param string $value The value to set for the custom profile field for
     *               testing.
     * @covers \local_guzzletest\api::get_profile_field_value
     * @dataProvider get_profile_field_value_exists_provider
     *
     * @return void
     */
    public function test_get_profile_field_value_exists(string $value): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        // Set the 'am' custom profile field value for the user.
        $shortname = api::$amshortname;
        $fieldname = 'profile_field_' . $shortname;
        $user->$fieldname = $value;

        // Save the user with the new profile field value.
        profile_save_data($user);

        $this->setUser($user);

        $api = new api();
        $result = $api->get_profile_field_value($shortname);

        $this->assertEquals($value, $result);
    }

    /**
     * Data provider for {@see test_get_profile_field_value_exists}
     *
     * @return array
     */
    public static function get_profile_field_value_exists_provider(): array {
        return [
            'user1' => ['654321'],
            'user2' => ['999999'],
            'user3' => [''],
        ];
    }

    /**
     * Test the 'get_profile_field_value' function when the specified profile
     * field value does not exist.
     *
     * This test method verifies that the 'get_profile_field_value' function
     * returns an empty string when attempting to retrieve the value of a
     * profile field that does not exist for the user.
     *
     * It performs the following steps:
     * 1. Sets up the necessary user context for testing.
     * 2. Calls the 'get_profile_field_value' function with the shortname of
     *    the custom profile field.
     * 3. Asserts that the result is an empty string, indicating the absence
     *    of the profile field.
     *
     * @covers \local_guzzletest\api::get_profile_field_value
     *
     * @return void
     */
    public function test_get_profile_field_value_not_exists(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $api = new api();
        $result = $api->get_profile_field_value(api::$amshortname);

        $this->assertEquals('', $result);
    }

    /**
     * Test the 'request_users' function.
     *
     * This test case verifies the behavior of the 'request_users' function
     * when making a simulated HTTP GET request to a URI with query parameters
     * and authentication headers using a mock HTTP client. It ensures that the
     * function correctly processes the API response and returns an array of
     * users when the request is successful and returns users.
     *
     * Test Steps:
     * 1. Define the expected base URI, page, limit, and bearer token for the
     *    mock request.
     * 2. Construct the expected URI with query parameters.
     * 3. Mock the HTTP client to simulate the API response with user data.
     * 4. Replace the actual HTTP client with the mock for testing.
     * 5. Call the 'request_users' function being tested.
     * 6. Assert that the result contains an array of users.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\api::request_users
     * @dataProvider request_users_provider
     *
     * @return void
     */
    public function test_request_users_returns_users(string $uri): void {
        // Define the expected bearer token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Create sample user data for the mock response.
        $userdata = [
            [
                'id' => 105,
                'firstName' => 'Aen1',
                'lastName' => 'Student1',
                'email' => 'edu1@test.gr',
                'am' => '4846',
                'afm' => '167558190',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 106,
                'firstName' => 'Aen2',
                'lastName' => 'Student2',
                'email' => 'edu2@test.gr',
                'am' => '5101',
                'afm' => '171022441',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
        ];

        // Mock the HTTP client to simulate the API response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () use ($userdata) {
            // Simulate a successful API response with user data.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['content' => $userdata])
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api();
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->request_users($uri, $token);

        // Assert that the result['content'] is an array containing user arrays.
        $this->assertIsArray($result['content']);
        $this->assertNotEmpty($result['content']);

        // Assert that all elements in the array have the expected keys.
        foreach ($result['content'] as $user) {
            $this->assertNotEmpty($user);
            $this->assertArrayHasKey('firstName', $user);
            $this->assertArrayHasKey('lastName', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('am', $user);
            $this->assertArrayHasKey('afm', $user);
            $this->assertArrayHasKey('academy', $user);
            $this->assertArrayHasKey('school', $user);
            $this->assertArrayHasKey('eduYear', $user);
            $this->assertArrayHasKey('eduPeriod', $user);
        }
    }

    /**
     * Test the 'request_users' function.
     *
     * This test case verifies the behavior of the 'request_users' function
     * when making a simulated HTTP GET request to a URI with query parameters
     * and authentication headers using a mock HTTP client. It ensures that the
     * function correctly processes the API response and returns an empty array
     * when the request is successful but returns no users.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\api::request_users
     * @dataProvider request_users_provider
     *
     * @return void
     */
    public function test_request_users_returns_empty(string $uri): void {
        // Define the expected bearer token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Create empty array for user data for the mock response.
        $userdata = [];

        // Mock the HTTP client to simulate the API response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () use ($userdata) {
            // Simulate a successful API response with user data.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['content' => $userdata])
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api();
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->request_users($uri, $token);

        // Assert that result['content'] is an empty array.
        $this->assertIsArray($result['content']);
        $this->assertEmpty($result['content']);
    }

    /**
     * Test the 'request_users' function.
     *
     * This test method simulates a scenario where the API returns an HTTP 200
     * response with invalid JSON data. It verifies that the function being
     * tested, 'request_users', handles this situation gracefully by returning
     * an empty array.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\api::request_users
     * @dataProvider request_users_provider
     *
     * @return void
     */
    public function test_request_users_invalid_json_response(string $uri): void {
        // Define the expected bearer token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Mock the HTTP client to simulate the API response with invalid JSON.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () {
            // Simulate a successful API response with invalid JSON.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                'invalid_json_data'
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api();
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->request_users($uri, $token);

        // Assert that result contains error and message.
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(200, $result['error']);

        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Received null response.', $result['message']);
    }

    /**
     * Data provider for
     * {@see test_request_users_returns_users}
     * {@see test_request_users_empty}
     * {@see test_request_users_invalid_json_response}
     *
     * @return array
     */
    public static function request_users_provider(): array {
        $baseuri = api::$baseuri . '/ws/students/list';
        $limit = api::$pagelimit;
        $am = '12345';

        return [
            'Many users' => ['uri' => "$baseuri?page=1&limit=$limit"],
            'Single user' => ['uri' => "$baseuri?am=$am"],
        ];
    }

    /**
     * Test the behavior of the 'get_users_from_api' method when it is called
     * with an invalid token, simulating a scenario where 'get_bearer_from_api'
     * returns false (no token).
     *
     * @covers \local_guzzletest\api::get_users_from_api
     *
     * @return void
     */
    public function test_get_users_from_api_invalid_token(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->onlyMethods(['get_bearer_from_api'])
            ->getMock();

        // Mock the get_bearer_from_api function to return false (no token).
        $apimock->method('get_bearer_from_api')->willReturn(false);

        // Call the get_users_from_api function.
        $result = $apimock->get_users_from_api(null, true);

        // Assert that the function returns false when there is no token.
        $this->assertFalse($result);
    }

    /**
     * Test case for the `get_users_from_api` function when `userid` is null,
     * which should return an array of user objects.
     *
     * The test mocks the `get_bearer_from_api` function to return a predefined
     * token, mocks the `request_users` function to return an array of user
     * data, and mocks the `create_user_objects` function to return user
     * objects.
     *
     * The function is expected to correctly retrieve user data from the API,
     * create user objects, and return them as an array.
     *
     * @covers \local_guzzletest\api::get_users_from_api
     *
     * @return void
     */
    public function test_get_users_from_api_userid_is_null_returns_users_objects(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->onlyMethods([
                'log_message',
                'get_bearer_from_api',
                'request_users',
                'create_user_objects',
            ])
            ->getMock();

        // Mock 'log_message' to do nothing.
        $apimock->method('log_message')
            ->willReturnCallback(function ($message) {
            });

        // Define the expected bearer token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Mock the get_bearer_from_api function to return a token.
        $apimock->method('get_bearer_from_api')->willReturn($token);

        // Define some expected users.
        $user1 = [
            'id' => 105,
            'firstName' => 'Aen1',
            'lastName' => 'Student1',
            'email' => 'edu1@test.gr',
            'am' => '4846',
            'afm' => '167558190',
            'academy' => 'AEN Macedonia',
            'school' => 'Shipping',
            'eduYear' => '2022-2023',
            'eduPeriod' => 'Semester A',
        ];

        $user2 = [
            'id' => 106,
            'firstName' => 'Aen2',
            'lastName' => 'Student2',
            'email' => 'edu2@test.gr',
            'am' => '5101',
            'afm' => '171022441',
            'academy' => 'AEN Macedonia',
            'school' => 'Shipping',
            'eduYear' => '2022-2023',
            'eduPeriod' => 'Semester A',
        ];

        // Mock the request_users function to return users.
        $response = [
            'pages' => 1,
            'status_code' => 200,
            'content' => [$user1, $user2],
        ];
        $apimock->method('request_users')->willReturn($response);

        // Mock the create_user_objects function to return user objects.
        $usersobjects = [(object) $user1, (object) $user2];
        $apimock->method('create_user_objects')->willReturn($usersobjects);

        // Call the get_users_from_api function.
        $result = $apimock->get_users_from_api(null, true);

        // Assert that the function returns an array of user objects.
        $this->assertIsArray($result);

        // Check if the user objects were correctly created.
        foreach ($result as $user) {
            $this->assertInstanceOf(\stdClass::class, $user);
        }
    }

    /**
     * Test case for the `get_users_from_api` function when `userid` exists,
     * which should return an array of user objects.
     *
     * The test mocks the `get_bearer_from_api` function to return a predefined
     * token, mocks the `request_users` function to return an array of user
     * data for a single user, mocks the `create_user_objects` function to
     * return a user object, and mocks the `get_profile_field_value` function
     * to return an am value.
     *
     * The function is expected to correctly retrieve user data for the specified
     * `userid`, create a user object, and return it as an array.
     *
     * @covers \local_guzzletest\api::get_users_from_api
     *
     * @return void
     */
    public function test_get_users_from_api_userid_exists_returns_users_objects(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->onlyMethods([
                'get_bearer_from_api',
                'request_users',
                'create_user_objects',
                'get_profile_field_value',
            ])
            ->getMock();

        // Define the expected bearer token.
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMj' .
            'M0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.Sfl' .
            'KxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        // Mock the get_bearer_from_api function to return a token.
        $apimock->method('get_bearer_from_api')->willReturn($token);

        // Define an expected user.
        $user1 = [
            'id' => 105,
            'firstName' => 'Aen1',
            'lastName' => 'Student1',
            'email' => 'edu1@test.gr',
            'am' => '4846',
            'afm' => '167558190',
            'academy' => 'AEN Macedonia',
            'school' => 'Shipping',
            'eduYear' => '2022-2023',
            'eduPeriod' => 'Semester A',
        ];

        // Mock the request_users function to return one user.
        $response = [
            'pages' => 1,
            'status_code' => 200,
            'content' => [$user1],
        ];
        $apimock->method('request_users')->willReturn($response);

        // Mock the create_user_objects function to return one user object.
        $userobject = [(object) $user1];
        $apimock->method('create_user_objects')->willReturn($userobject);

        // Mock the get_profile_field_value function to return the am of the user.
        $apimock->method('get_profile_field_value')->willReturn($user1['am']);

        // Call the get_users_from_api function.
        $userid = 105;
        $result = $apimock->get_users_from_api($userid, true);

        // Assert that the function returns an array of user objects.
        $this->assertIsArray($result);

        // Check if the user object was correctly created.
        $this->assertInstanceOf(\stdClass::class, $result[0]);
    }

    /**
     * Test the 'create_user_objects' method with valid user data.
     *
     * This test case verifies that the 'create_user_objects' method can
     * correctly transform an array of user data into an array of user objects.
     * The provided user data includes all expected fields, such as
     * 'firstName', 'lastName', 'email', 'am', 'afm', 'academy', 'school',
     * 'eduYear', and 'eduPeriod'. The test checks if the method sets these
     * fields in the user objects and also correctly handles custom profile
     * fields (e.g., 'profile_field_am').
     *
     * Test steps:
     * 1. Instantiate the 'api' object.
     * 2. Define an array of user data, each containing various user attributes.
     * 3. Call the 'create_user_objects' method with the user data.
     * 4. Assert that result['content'] is an array containing user objects.
     * 5. Verify that the user objects have the expected properties and values.
     *
     * @covers \local_guzzletest\api::create_user_objects
     *
     * @return void
     */
    public function test_create_user_objects_with_valid_data(): void {
        $api = new api();
        $userdata = [
            [
                'id' => 105,
                'firstName' => 'Aen1',
                'lastName' => 'Student1',
                'email' => 'edu1@test.gr',
                'am' => '4846',
                'afm' => '167558190',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 106,
                'firstName' => 'Aen2',
                'lastName' => 'Student2',
                'email' => 'edu2@test.gr',
                'am' => '5101',
                'afm' => '171022441',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
        ];

        $result = $api->create_user_objects($userdata);

        $this->assertCount(2, $result);

        $this->assertObjectHasAttribute('profile_field_am', $result[0]);
        $this->assertObjectHasAttribute('email', $result[0]);
        $this->assertEquals('Aen1', $result[0]->firstname);
        $this->assertEquals('Student1', $result[0]->lastname);
        $this->assertEquals('edu1@test.gr', $result[0]->email);
        $this->assertEquals('4846', $result[0]->profile_field_am);
        $this->assertEquals('167558190', $result[0]->profile_field_afm);
        $this->assertEquals('AEN Macedonia', $result[0]->profile_field_academy);
        $this->assertEquals('Shipping', $result[0]->profile_field_school);
        $this->assertEquals('2022-2023', $result[0]->profile_field_eduyear);
        $this->assertEquals('Semester A', $result[0]->profile_field_eduperiod);

        $this->assertObjectHasAttribute('profile_field_am', $result[1]);
        $this->assertObjectHasAttribute('email', $result[1]);
        $this->assertEquals('Aen2', $result[1]->firstname);
        $this->assertEquals('Student2', $result[1]->lastname);
        $this->assertEquals('edu2@test.gr', $result[1]->email);
        $this->assertEquals('5101', $result[1]->profile_field_am);
        $this->assertEquals('171022441', $result[1]->profile_field_afm);
        $this->assertEquals('AEN Macedonia', $result[1]->profile_field_academy);
        $this->assertEquals('Shipping', $result[1]->profile_field_school);
        $this->assertEquals('2022-2023', $result[1]->profile_field_eduyear);
        $this->assertEquals('Semester A', $result[1]->profile_field_eduperiod);
    }

    /**
     * Test the 'create_user_objects' method when the 'am' field is missing
     * or is empty.
     *
     * This test case validates the behavior of the 'create_user_objects'
     * method when the 'am' field is missing or is empty in the provided user
     * data. It checks that the method correctly handles this required 'am'
     * field by skipping the user.
     *
     * Test steps:
     * 1. Instantiate the 'api' object.
     * 2. Define an array of user data, with varying 'am' values, including
     *    missing or empty.
     * 3. Call the 'create_user_objects' method with the user data.
     * 4. Assert that result['content'] is an array containing user objects.
     * 5. Verify that the user object properties are correctly set for the user
     *    with a valid 'am'.
     * 6. Ensure that the user is skipped for missing or empty 'am' field.
     *
     * @covers \local_guzzletest\api::create_user_objects
     *
     * @return void
     */
    public function test_create_user_objects_missing_am_field(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->onlyMethods([
                'log_message',
            ])
            ->getMock();

        // Mock 'log_message' to do nothing.
        $apimock->method('log_message')
            ->willReturnCallback(function ($message) {
            });

        $userdata = [
            [
                'id' => 105,
                'firstName' => 'Aen1',
                'lastName' => 'Student1',
                'email' => 'edu1@test.gr',
                'afm' => '167558190',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 106,
                'firstName' => 'Aen2',
                'lastName' => 'Student2',
                'email' => 'edu2@test.gr',
                'am' => '',
                'afm' => '171022441',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 107,
                'firstName' => 'Aen3',
                'lastName' => 'Student3',
                'email' => 'edu3@test.gr',
                'am' => '5142',
                'afm' => '174497438',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
        ];

        $result = $apimock->create_user_objects($userdata);

        $this->assertCount(1, $result);

        $this->assertObjectHasAttribute('profile_field_am', $result[0]);
        $this->assertObjectHasAttribute('email', $result[0]);
        $this->assertEquals('Aen3', $result[0]->firstname);
        $this->assertEquals('Student3', $result[0]->lastname);
        $this->assertEquals('edu3@test.gr', $result[0]->email);
        $this->assertEquals('5142', $result[0]->profile_field_am);
        $this->assertEquals('174497438', $result[0]->profile_field_afm);
        $this->assertEquals('AEN Macedonia', $result[0]->profile_field_academy);
        $this->assertEquals('Shipping', $result[0]->profile_field_school);
        $this->assertEquals('2022-2023', $result[0]->profile_field_eduyear);
        $this->assertEquals('Semester A', $result[0]->profile_field_eduperiod);
    }

    /**
     * Test the 'create_user_objects' method when the 'email' field is missing
     * or is empty.
     *
     * This test case evaluates the behavior of the 'create_user_objects'
     * method when the 'email' field is missing or is empty in the provided
     * user data. It ensures that the method correctly handles these scenarios,
     * by skipping the user.
     *
     * Test steps:
     * 1. Instantiate the 'api' object.
     * 2. Define an array of user data, with varying 'email' values, including
     *    missing or empty.
     * 3. Call the 'create_user_objects' method with the user data.
     * 4. Assert that result['content'] is an array containing user objects.
     * 5. Verify that the user object properties are correctly set for the user
     *    with a valid 'email'.
     * 6. Ensure that the user is skipped for missing or empty 'email' field.
     *
     * @covers \local_guzzletest\api::create_user_objects
     *
     * @return void
     */
    public function test_create_user_objects_missing_email_field(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->onlyMethods([
                'log_message',
            ])
            ->getMock();

        // Mock 'log_message' to do nothing.
        $apimock->method('log_message')
            ->willReturnCallback(function ($message) {
            });

        $userdata = [
            [
                'id' => 105,
                'firstName' => 'Aen1',
                'lastName' => 'Student1',
                'am' => '4846',
                'afm' => '167558190',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 106,
                'firstName' => 'Aen2',
                'lastName' => 'Student2',
                'email' => 'edu2@test.gr',
                'am' => '5101',
                'afm' => '171022441',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
            [
                'id' => 107,
                'firstName' => 'Aen3',
                'lastName' => 'Student3',
                'email' => '',
                'am' => '5142',
                'afm' => '174497438',
                'academy' => 'AEN Macedonia',
                'school' => 'Shipping',
                'eduYear' => '2022-2023',
                'eduPeriod' => 'Semester A',
            ],
        ];

        $result = $apimock->create_user_objects($userdata);

        $this->assertCount(1, $result);

        $this->assertObjectHasAttribute('profile_field_am', $result[0]);
        $this->assertObjectHasAttribute('email', $result[0]);
        $this->assertEquals('Aen2', $result[0]->firstname);
        $this->assertEquals('Student2', $result[0]->lastname);
        $this->assertEquals('edu2@test.gr', $result[0]->email);
        $this->assertEquals('5101', $result[0]->profile_field_am);
        $this->assertEquals('171022441', $result[0]->profile_field_afm);
        $this->assertEquals('AEN Macedonia', $result[0]->profile_field_academy);
        $this->assertEquals('Shipping', $result[0]->profile_field_school);
        $this->assertEquals('2022-2023', $result[0]->profile_field_eduyear);
        $this->assertEquals('Semester A', $result[0]->profile_field_eduperiod);
    }

    /**
     * Test the 'log_user_update method' for a successful database record
     * insertion.
     *
     * This test case verifies that the 'log_user_update method' correctly
     * interacts with the database and returns the expected record ID upon a
     * successful insert_record operation.
     *
     * @covers \local_guzzletest\api::log_user_update
     *
     * @return void
     */
    public function test_log_user_update_success(): void {
        global $DB;

        // Mock the database.
        $DB = $this->getMockBuilder('moodle_database')
            ->getMock();

        // Define the expected record object.
        $userid = 123;
        $afm = 456;
        $am = 789;
        $time = new \DateTime('now');
        $timestamp = $time->getTimestamp();

        $expectedrecord = new \stdClass();
        $expectedrecord->userid = $userid;
        $expectedrecord->afm = $afm;
        $expectedrecord->am = $am;
        $expectedrecord->timecreated = $timestamp;
        $recordid = 42;

        // Expect the insert_record method to be called with the correct arguments.
        $DB->expects($this->once())
            ->method('insert_record')
            ->with(
                $this->equalTo('local_guzzletest_userlog'),
                $this->equalTo($expectedrecord)
            )
            ->willReturn($recordid);

        // Call the log_user_update method.
        $api = new api();
        $result = $api->log_user_update($userid, $afm, $am);

        // Assert that the function returns the mock record ID.
        $this->assertEquals($recordid, $result);
    }

    /**
     * Clean up after all tests have finished.
     *
     * @return void
     */
    protected function tearDown(): void {
    }
}
