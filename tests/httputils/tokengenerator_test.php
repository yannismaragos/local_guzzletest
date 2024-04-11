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

use local_guzzletest\httputils\Config;
use local_guzzletest\httputils\Tokengenerator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use ReflectionClass;
use InvalidArgumentException;
use Exception;
use JsonException;

/**
 * Tests for the Tokengenerator class.
 *
 * @package    local_guzzletest
 * @category   test
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_guzzletest\httputils\Tokengenerator
 */
class tokengenerator_test extends \advanced_testcase {
    /**
     * The base URI.
     *
     * @var string
     */
    private $baseuri;

    /**
     * The mock client object used for testing purposes.
     *
     * @var Client
     */
    private $mockclient;

    /**
     * The configuration object.
     *
     * @var Config
     */
    private $config;

    /**
     * Setup tasks for all tests.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->baseuri = 'https://base.api.uri';
        $this->mockclient = $this->createMock(Client::class);
        $this->config = Config::get_instance($this->baseuri, $this->mockclient);
    }

    /**
     * Test case for the 'get_default_auth_headers' method.
     *
     * This method tests the functionality of the 'get_default_auth_headers' method
     * in the Handler class. It verifies that the method returns the expected
     * default authentication headers.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_default_auth_headers
     */
    public function test_get_default_auth_headers(): void {
        $expectedheaders = [
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

        $tokengenerator = new Tokengenerator($this->config);
        $reflection = new ReflectionClass(Tokengenerator::class);
        $method = $reflection->getMethod('get_default_auth_headers');
        $method->setAccessible(true);
        $headers = $method->invokeArgs($tokengenerator, []);

        $this->assertEquals($expectedheaders, $headers);
    }

    /**
     * Test the 'set_authentication_headers' method of the Tokengenerator class.
     *
     * This test verifies that the 'set_authentication_headers' method correctly
     * sets the authentication headers on the Tokengenerator object.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::set_authentication_headers
     */
    public function test_set_authentication_headers(): void {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'MyApp/1.0.0',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'no-cache',
        ];

        $tokengenerator = new Tokengenerator($this->config);
        $tokengenerator->set_authentication_headers($headers);

        // Use reflection to access the private property authheaders.
        $reflection = new ReflectionClass(Tokengenerator::class);
        $property = $reflection->getProperty('authheaders');
        $property->setAccessible(true);
        $authheaders = $property->getValue($tokengenerator);

        $this->assertEquals($headers, $authheaders);
    }

    /**
     * Test case for the 'get_bearer_token' method when the username or password is empty.
     *
     * @throws InvalidArgumentException When the username or password is empty.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_empty_username_or_password(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing credentials in get_bearer_token method.');

        $username = '';
        $password = '';
        $tokengenerator = new Tokengenerator($this->config);
        $tokengenerator->get_bearer_token($username, $password);
    }

    /**
     * Test case for the 'get_bearer_token' method when the HTTP client is null.
     *
     * @throws Exception When the HTTP client is null.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_null_client(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('HTTP client is null.');

        // Create a stub for the Config class.
        $configstub = $this->createStub(Config::class);

        $configstub->method('get_setting')
            ->willReturnCallback(function ($arg) {
                if ($arg === 'EXCEPTION_CODE_CLIENT') {
                    return 4004;
                }
            });

        $configstub->method('get_http_client')
            ->willReturn(null);

        $tokengenerator = new Tokengenerator($configstub);
        $tokengenerator->get_bearer_token('username', 'password');
    }

    /**
     * Test case for the 'get_bearer_token' method when the connection to the API fails.
     *
     * @throws Exception When the connection to the API fails.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_connection_failed(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to connect to API.');

        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Create a mock for the Client class.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')
            ->willThrowException(new RequestException(
                'Failed to connect to API.',
                new Request('GET', $this->baseuri)
            ));

        // Set the Config singleton instance with the mock client.
        $configmock = Config::get_instance($this->baseuri, $clientmock);

        $tokengenerator = new Tokengenerator($configmock);
        $tokengenerator->get_bearer_token('username', 'password');
    }

    /**
     * Test case for the 'get_bearer_token' method when the status code is not 200.
     *
     * @throws Exception When the API request fails with a status code of 500.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_status_code_not_200(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API request failed with status code: 500');

        // Mock the client to return an error 500 response.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturnCallback(
            function () {
                $responsemock = new Response(
                    500,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => 'An error occurred.'])
                );

                return $responsemock;
            }
        );

        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Set the Config singleton instance with the mock client.
        $configmock = Config::get_instance($this->baseuri, $clientmock);

        $tokengenerator = new Tokengenerator($configmock);
        $tokengenerator->get_bearer_token('username', 'password');
    }

    /**
     * Test case for the 'get_bearer_token' method when JSON decoding fails.
     *
     * This test case ensures that an exception of type JsonException is thrown
     * when attempting to decode invalid JSON from the API response.
     *
     * @throws JsonException When JSON decoding fails.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_json_decode_failed(): void {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Failed to decode JSON from API response.');

        // Mock the response to return invalid json.
        $responsemock = $this->createMock(Response::class);
        $responsemock->method('getBody')->willReturn(Utils::streamFor('{invalid json}'));
        $responsemock->method('getStatusCode')->willReturn(200);

        // Mock the client to return the response mock.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturn($responsemock);

        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Set the Config singleton instance with the mock client.
        $configmock = Config::get_instance($this->baseuri, $clientmock);

        $tokengenerator = new Tokengenerator($configmock);
        $tokengenerator->get_bearer_token('username', 'password');
    }

    /**
     * Test case for the 'get_bearer_token' method when the token is not found
     * in the API response.
     *
     * @throws Exception When the token is not found in the API response.
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_not_found_token(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Bearer token not found in API response.');

        // Mock the response to return the records.
        $responsemock = $this->createMock(Response::class);
        $responsemock->method('getBody')->willReturn(Utils::streamFor(
            json_encode(['mykey' => 'myvalue'])
        ));
        $responsemock->method('getStatusCode')->willReturn(200);

        // Mock the client to return the response mock.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturn($responsemock);

        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Set the Config singleton instance with the mock client.
        $configmock = Config::get_instance($this->baseuri, $clientmock);

        $tokengenerator = new Tokengenerator($configmock);
        $tokengenerator->get_bearer_token('username', 'password');
    }

    /**
     * Test case for the 'get_bearer_token' method when the token is found
     * in the API response.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Tokengenerator::get_bearer_token
     */
    public function test_get_bearer_token_success(): void {
        $expectedtoken = '123456';

        // Mock the response to return the records.
        $responsemock = $this->createMock(Response::class);
        $responsemock->method('getBody')->willReturn(Utils::streamFor(
            json_encode(['token' => '123456'])
        ));
        $responsemock->method('getStatusCode')->willReturn(200);

        // Mock the client to return the response mock.
        $clientmock = $this->createMock(Client::class);
        $clientmock->method('request')->willReturn($responsemock);

        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Set the Config singleton instance with the mock client.
        $configmock = Config::get_instance($this->baseuri, $clientmock);

        $tokengenerator = new Tokengenerator($configmock);
        $token = $tokengenerator->get_bearer_token('username', 'password');

        $this->assertEquals($expectedtoken, $token);
    }

    /**
     * Clean up after all tests have finished.
     *
     * @return void
     */
    protected function tearDown(): void {
        // Reset the Config singleton instance to null.
        $reflection = new ReflectionClass(Config::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
    }
}
