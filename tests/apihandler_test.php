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

use local_guzzletest\Apihandler as api;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests for the Apihandler class.
 *
 * @package    local_guzzletest
 * @category   test
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_guzzletest\Apihandler
 */
class apihandler_test extends \advanced_testcase {
    /**
     * The base URI.
     *
     * @var string
     */
    private static $baseuri = 'http://universities.hipolabs.com';

    /**
     * Results per page.
     *
     * @var int
     */
    private static $pagelimit = 10;

    /**
     * Username for ldap login.
     *
     * @var string
     */
    private static $username = 'myuser';

    /**
     * Password for ldap login.
     *
     * @var string
     */
    private static $password = 'mypassword';

    /**
     * The expected bearer token.
     *
     * @var string
     */
    private static $token = 'eyJhbGciOiJIUzI3NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxND' .
        'U2MzQ1Nzg5IiwibmFtZSI6Ik5hbSBTdXBlciIsImlhdCI6MTUxNjIzOTAyMn0.' .
        'Kfl7xwRSJSMeKK2P4fqpwSfJM36POkVySFa_qJssw5c';

    /**
     * Setup tasks for all tests.
     *
     * @return void
     */
    protected function setUp(): void {
    }

    /**
     * Test the `get_bearer_from_api` function with dummy token.
     *
     * This method tests the functionality of retrieving a bearer token from the API using a dummy token.
     * It verifies that the correct bearer token is returned and that the API call is successful.
     *
     * @covers \local_guzzletest\Apihandler::get_bearer_from_api
     *
     * @return void
     */
    public function test_get_bearer_from_api_with_dummy_token() {
        $api = new api(self::$baseuri);
        $api->set_dummy_token(self::$token);

        $result = $api->get_bearer_from_api(self::$username, self::$password);

        $this->assertEquals(self::$token, $result);
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
     * @covers \local_guzzletest\Apihandler::get_bearer_from_api
     *
     * @return void
     */
    public function test_get_bearer_from_api_success(): void {
        $token = self::$token;

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
        $api = new api(self::$baseuri);
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_bearer_from_api(self::$username, self::$password);

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
     * @covers \local_guzzletest\Apihandler::get_bearer_from_api
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
        $api = new api(self::$baseuri);
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_bearer_from_api(self::$username, self::$password);

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
     * Test the 'get_data_from_api' function.
     *
     * This test case verifies the behavior of the 'get_data_from_api' function
     * when making a simulated HTTP GET request to a URI with query parameters
     * and authentication headers using a mock HTTP client. It ensures that the
     * function correctly processes the API response and returns results.
     *
     * Test Steps:
     * 1. Define the expected base URI, page, limit for the mock request.
     * 2. Construct the expected URI with query parameters.
     * 3. Mock the HTTP client to simulate the API response with data.
     * 4. Replace the actual HTTP client with the mock for testing.
     * 5. Call the 'get_data_from_api' function being tested.
     * 6. Assert that the result contains an array of results.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\Apihandler::get_data_from_api
     * @dataProvider get_data_from_api_provider
     *
     * @return void
     */
    public function test_get_data_from_api_returns_results(string $uri): void {
        // Create sample data for the mock response.
        $data = [
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
        $clientmock->method('request')->willReturnCallback(function () use ($data) {
            // Simulate a successful API response with data.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['content' => $data])
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api(self::$baseuri);
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_data_from_api($uri, self::$token);

        // Assert that the result['content'] is an array containing arrays.
        $this->assertIsArray($result['content']);
        $this->assertNotEmpty($result['content']);

        // Assert that all elements in the array have the expected keys.
        foreach ($result['content'] as $result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('firstName', $result);
            $this->assertArrayHasKey('lastName', $result);
            $this->assertArrayHasKey('email', $result);
            $this->assertArrayHasKey('am', $result);
            $this->assertArrayHasKey('afm', $result);
            $this->assertArrayHasKey('academy', $result);
            $this->assertArrayHasKey('school', $result);
            $this->assertArrayHasKey('eduYear', $result);
            $this->assertArrayHasKey('eduPeriod', $result);
        }
    }

    /**
     * Test the 'get_data_from_api' function.
     *
     * This test case verifies the behavior of the 'get_data_from_api' function
     * when making a simulated HTTP GET request to a URI with query parameters
     * and authentication headers using a mock HTTP client. It ensures that the
     * function correctly processes the API response and returns an empty array
     * when the request is successful but returns no results.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\Apihandler::get_data_from_api
     * @dataProvider get_data_from_api_provider
     *
     * @return void
     */
    public function test_get_data_from_api_returns_empty(string $uri): void {
        // Create empty array for data for the mock response.
        $data = [];

        // Mock the HTTP client to simulate the API response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(function () use ($data) {
            // Simulate a successful API response with data.
            $responsemock = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['content' => $data])
            );

            return $responsemock;
        });

        // Replace the actual HTTP client with the mock.
        $api = new api(self::$baseuri);
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_data_from_api($uri, self::$token);

        // Assert that result['content'] is an empty array.
        $this->assertIsArray($result['content']);
        $this->assertEmpty($result['content']);
    }

    /**
     * Test the 'get_data_from_api' function.
     *
     * This test method simulates a scenario where the API returns an HTTP 200
     * response with invalid JSON data. It verifies that the function being
     * tested, 'get_data_from_api', handles this situation gracefully by returning
     * an empty array.
     *
     * @param string $uri The URI to send the API request to.
     * @covers \local_guzzletest\Apihandler::get_data_from_api
     * @dataProvider get_data_from_api_provider
     *
     * @return void
     */
    public function test_get_data_from_api_invalid_json_response(string $uri): void {
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
        $api = new api(self::$baseuri);
        $api->set_http_client($clientmock);

        // Call the function being tested.
        $result = $api->get_data_from_api($uri, self::$token);

        // Assert that result contains error and message.
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(200, $result['error']);

        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Received null response.', $result['message']);
    }

    /**
     * Data provider for
     * {@see test_get_data_from_api_returns_results}
     * {@see test_get_data_from_api_empty}
     * {@see test_get_data_from_api_invalid_json_response}
     *
     * @return array
     */
    public static function get_data_from_api_provider(): array {
        $baseuri = self::$baseuri . '/search';
        $limit = self::$pagelimit;
        $am = '12345';

        return [
            'Many results' => ['uri' => "$baseuri?page=1&limit=$limit"],
            'Single result' => ['uri' => "$baseuri?am=$am"],
        ];
    }

    /**
     * Test the behavior of the 'get_paginated_data' method when it is called
     * with an invalid token, simulating a scenario where 'get_bearer_from_api'
     * returns false (no token).
     *
     * @covers \local_guzzletest\Apihandler::get_paginated_data
     *
     * @return void
     */
    public function test_get_paginated_data_invalid_token(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->setConstructorArgs([self::$baseuri])
            ->onlyMethods(['get_bearer_from_api'])
            ->getMock();

        // Mock the get_bearer_from_api function to return false (no token).
        $apimock->method('get_bearer_from_api')->willReturn(false);

        // Call the get_paginated_data function.
        $result = $apimock->get_paginated_data(self::$username, self::$password);

        // Assert that the function returns false when there is no token.
        $this->assertFalse($result);
    }

    /**
     * Test case for the `get_paginated_data` function.
     *
     * The test mocks the `get_bearer_from_api` function to return a predefined
     * token, mocks the `get_data_from_api` function to return an array of data,
     * and mocks the `create_objects` function to return objects.
     *
     * The function is expected to correctly retrieve data from the API,
     * create objects, and return them as an array.
     *
     * @covers \local_guzzletest\Apihandler::get_paginated_data
     *
     * @return void
     */
    public function test_get_paginated_data_returns_objects(): void {
        // Create a mock for the api class.
        $apimock = $this->getMockBuilder(api::class)
            ->setConstructorArgs([self::$baseuri])
            ->onlyMethods([
                'log_message',
                'get_bearer_from_api',
                'get_data_from_api',
                'create_objects',
            ])
            ->getMock();

        // Mock 'log_message' to do nothing.
        $apimock->method('log_message')->willReturnCallback(function ($message) {
            // Do nothing.
        });

        // Mock the get_bearer_from_api function to return a token.
        $apimock->method('get_bearer_from_api')->willReturn(self::$token);

        // Define some expected results.
        $result1 = [
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

        $result2 = [
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

        // Mock the get_data_from_api function to return results.
        $response = [
            'pages' => 1,
            'status_code' => 200,
            'content' => [$result1, $result2],
        ];
        $apimock->method('get_data_from_api')->willReturn($response);

        // Mock the create_objects function to return objects.
        $objects = [(object) $result1, (object) $result2];
        $apimock->method('create_objects')->willReturn($objects);

        // Call the get_paginated_data function.
        $result = $apimock->get_paginated_data(self::$username, self::$password);

        // Assert that the function returns an array of objects.
        $this->assertIsArray($result);

        // Check if the objects were correctly created.
        foreach ($result as $object) {
            $this->assertInstanceOf(\stdClass::class, $object);
        }
    }

    /**
     * Clean up after all tests have finished.
     *
     * @return void
     */
    protected function tearDown(): void {
    }
}
