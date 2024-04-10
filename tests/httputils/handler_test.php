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
use local_guzzletest\httputils\Handler;
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
 * Tests for the Handler class.
 *
 * @package    local_guzzletest
 * @category   test
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_guzzletest\httputils\Handler
 */
class handler_test extends \advanced_testcase {
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
     * The bearer token.
     *
     * @var string
     */
    private $token;

    /**
     * Setup tasks for all tests.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->baseuri = 'https://base.api.uri';
        $this->mockclient = $this->createMock(Client::class);
        $this->config = Config::get_instance($this->baseuri, $this->mockclient);
        $this->token = 'eyJhbGciOiJIUzI3NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxND' .
            'U2MzQ1Nzg5IiwibmFtZSI6Ik5hbSBTdXBlciIsImlhdCI6MTUxNjIzOTAyMn0.' .
            'Kfl7xwRSJSMeKK2P4fqpwSfJM36POkVySFa_qJssw5c';
    }

    /**
     * Test case for the 'get_default_request_headers' method.
     *
     * This method tests the functionality of the 'get_default_request_headers'
     * method in the Handler class. It verifies that the method returns the
     * expected default request headers.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_default_request_headers
     */
    public function test_get_default_request_headers(): void {
        $expectedheaders = [
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

        // Use reflection to access the private method get_default_request_headers.
        $handler = new Handler($this->config);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_default_request_headers');
        $method->setAccessible(true);
        $headers = $method->invokeArgs($handler, []);

        $this->assertEquals($expectedheaders, $headers);
    }

    /**
     * Test case for the 'get_default_response_schema' method.
     *
     * This method tests the functionality of the 'get_default_response_schema'
     * method in the Handler class. It verifies that the method returns the
     * default response schema as expected.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_default_response_schema
     */
    public function test_get_default_response_schema(): void {
        $expectedschema = [
            'page_number' => 'page',
            'page_limit' => 'limit',
            'total_records' => 'total',
            'records' => 'records',
        ];

        // Use reflection to access the private method get_default_response_schema.
        $handler = new Handler($this->config);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_default_response_schema');
        $method->setAccessible(true);
        $schema = $method->invokeArgs($handler, []);

        $this->assertEquals($expectedschema, $schema);
    }

    /**
     * Test case for the 'set_response_schema' method.
     *
     * This method tests the functionality of the 'set_response_schema' method
     * in the Handler class. It verifies that the response schema is correctly set.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::set_response_schema
     * @covers \local_guzzletest\httputils\Handler::get_response_schema
     */
    public function test_set_response_schema(): void {
        $schema = [
            'page_number' => 'mypage',
            'page_limit' => 'mylimit',
            'total_records' => 'mytotal',
            'records' => 'myrecords',
        ];

        $handler = new Handler($this->config);
        $handler->set_response_schema($schema);
        $actualschema = $handler->get_response_schema();

        $this->assertEquals($schema, $actualschema);
    }

    /**
     * Test case for the 'authenticate' method.
     *
     * This method tests the behavior of the 'authenticate' method in the
     * 'handler' class. It verifies that the method handles the scenario
     * when the authentication credentials are missing.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::authenticate
     */
    public function test_authenticate_missing_credentials(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing credentials in authenticate method.');

        // Create a stub for the Tokengenerator class.
        $tokengeneratorstub = $this->createStub(Tokengenerator::class);

        $handler = new Handler($this->config);
        $handler->authenticate([], $tokengeneratorstub);
    }

    /**
     * Test case for the 'authenticate' method in the Handler class.
     *
     * This test verifies that the 'authenticate' method correctly sets the
     * authorization header with a bearer token obtained from the Tokengenerator class.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::authenticate
     */
    public function test_authenticate_success(): void {
        $credentials = [
            'username' => 'myuser',
            'password' => 'mypassword',
            'endpoint' => 'myendpoint',
        ];

        // Create a stub for the Tokengenerator class.
        $tokengeneratorstub = $this->createStub(Tokengenerator::class);
        $tokengeneratorstub->method('get_bearer_token')->willReturn($this->token);

        $handler = new Handler($this->config);
        $handler->authenticate($credentials, $tokengeneratorstub);

        // Use reflection to access the private property requestheaders['authorization'].
        $reflection = new ReflectionClass(Handler::class);
        $property = $reflection->getProperty('requestheaders');
        $property->setAccessible(true);
        $requestheaders = $property->getValue($handler);

        $this->assertArrayHasKey('authorization', $requestheaders);
        $this->assertEquals("Bearer $this->token", $requestheaders['authorization']);
    }

    /**
     * Test the 'set_request_headers' method of the Handler class.
     *
     * This test verifies that the 'set_request_headers' method correctly sets
     * the request headers on the Handler object.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::set_request_headers
     */
    public function test_set_request_headers(): void {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'MyApp/1.0.0',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'no-cache',
        ];

        $handler = new Handler($this->config);
        $handler->set_request_headers($headers);

        // Use reflection to access the private property requestheaders.
        $reflection = new ReflectionClass(Handler::class);
        $property = $reflection->getProperty('requestheaders');
        $property->setAccessible(true);
        $requestheaders = $property->getValue($handler);

        $this->assertEquals($headers, $requestheaders);
    }

    /**
     * Test case for the 'get_data_from_uri' method when the URI is empty.
     *
     * @throws InvalidArgumentException When the URI is empty.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_empty_uri(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI.');

        $uri = '';
        $handler = new Handler($this->config);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$uri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method when the URI is invalid.
     *
     * @throws InvalidArgumentException When the URI is invalid.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_invalid_uri(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI.');

        $uri = 'not-a-uri';
        $handler = new Handler($this->config);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$uri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method when the HTTP client is null.
     *
     * @throws Exception When the HTTP client is null.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_null_client(): void {
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

        $handler = new Handler($configstub);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$this->baseuri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method in the Handler class.
     * This test verifies that an exception is thrown when the connection to the API fails.
     *
     * @throws Exception When the connection to the API fails.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_connection_failed(): void {
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

        $handler = new Handler($configmock);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$this->baseuri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method when the status code is not 200.
     *
     * @throws Exception When the API request fails with a status code of 500.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_status_code_not_200(): void {
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

        $handler = new Handler($configmock);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$this->baseuri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method when JSON decoding fails.
     *
     * This test case ensures that an exception of type JsonException is thrown
     * when attempting to decode invalid JSON from the API response.
     *
     * @throws JsonException When JSON decoding fails.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     */
    public function test_get_data_from_uri_json_decode_failed(): void {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Failed to decode JSON from API response: Syntax error');

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

        $handler = new Handler($configmock);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $method->invokeArgs($handler, [$this->baseuri]);
    }

    /**
     * Test case for the 'get_data_from_uri' method in the Handler class.
     *
     * This test verifies that the 'get_data_from_uri' method correctly retrieves
     * data from a URI. It creates sample records for the mock response and sets
     * up the necessary mocks for the client and response. It then invokes the
     * 'get_data_from_uri' method using reflection and asserts that the result is
     * an array containing the expected records with the correct keys.
     *
     * @param array $expectedrecords The records we expect 'get_page' to return.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_data_from_uri
     * @dataProvider get_page_provider
     */
    public function test_get_data_from_uri_success($expectedrecords): void {
        // Mock the response to return the records.
        $responsemock = $this->createMock(Response::class);
        $responsemock->method('getBody')->willReturn(Utils::streamFor(
            json_encode($expectedrecords)
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

        $handler = new Handler($configmock);
        $reflection = new ReflectionClass(Handler::class);
        $method = $reflection->getMethod('get_data_from_uri');
        $method->setAccessible(true);
        $records = $method->invokeArgs($handler, [$this->baseuri]);

        // Assert that the result is an array containing the records.
        $this->assertIsArray($records);
        $this->assertNotEmpty($records);

        // Assert that all elements in the array have the expected keys.
        foreach ($records as $record) {
            $this->assertNotEmpty($record);
            $this->assertArrayHasKey('state-province', $record);
            $this->assertArrayHasKey('country', $record);
            $this->assertArrayHasKey('domains', $record);
            $this->assertArrayHasKey('web_pages', $record);
            $this->assertArrayHasKey('alpha_two_code', $record);
            $this->assertArrayHasKey('name', $record);
        }
    }

    /**
     * Tests the 'get_page' method of the Handler class.
     *
     * This test creates a partial mock of the Handler class, with the
     * 'get_data_from_uri' method mocked to return a predefined set of records.
     * It then calls the 'get_page' method on the mock object and asserts that
     * the returned result matches the expected records.
     *
     * @param array $expectedrecords The records we expect 'get_page' to return.
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_page
     * @dataProvider get_page_provider
     */
    public function test_get_page($expectedrecords): void {
        // Create a partial mock for the Handler class.
        $handlermock = $this->getMockBuilder(Handler::class)
            ->setConstructorArgs([$this->config])
            ->onlyMethods(['get_data_from_uri'])
            ->getMock();

        $handlermock->method('get_data_from_uri')
            ->willReturn(['records' => $expectedrecords]);

        $result = $handlermock->get_page();

        $this->assertEquals($expectedrecords, $result);
    }

    /**
     * Data provider for
     * {@see test_get_data_from_uri_success}
     * {@see test_get_page}
     *
     * @return array
     */
    public static function get_page_provider(): array {
        return [
            [
                [
                    [
                        'state-province' => 'Aegean Sea',
                        'country' => 'Greece',
                        'domains' => ['aegean.gr'],
                        'web_pages' => ['https://www.aegean.gr/'],
                        'alpha_two_code' => 'GR',
                        'name' => 'Aegean University',
                    ],
                    [
                        'state-province' => 'Attica',
                        'country' => 'Greece',
                        'domains' => ['ntua.gr'],
                        'web_pages' => ['https://www.ntua.gr/'],
                        'alpha_two_code' => 'GR',
                        'name' => 'National Technical University of Athens',
                    ],
                    [
                        'state-province' => null,
                        'country' => 'Greece',
                        'domains' => ['eap.gr'],
                        'web_pages' => ['https://www.eap.gr/'],
                        'alpha_two_code' => 'GR',
                        'name' => 'Hellenic Open University',
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests the 'get_all_pages' method of the Handler class.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Handler::get_all_pages
     */
    public function test_get_all_pages(): void {
        // First page of records.
        $page1 = [
            [
                'state-province' => 'Aegean Sea',
                'country' => 'Greece',
                'domains' => ['aegean.gr'],
                'web_pages' => ['https://www.aegean.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'Aegean University',
            ],
            [
                'state-province' => 'Attica',
                'country' => 'Greece',
                'domains' => ['ntua.gr'],
                'web_pages' => ['https://www.ntua.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'National Technical University of Athens',
            ],
            [
                'state-province' => null,
                'country' => 'Greece',
                'domains' => ['eap.gr'],
                'web_pages' => ['https://www.eap.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'Hellenic Open University',
            ],
        ];

        // Second page of records.
        $page2 = [
            [
                'state-province' => 'Crete',
                'country' => 'Greece',
                'domains' => ['uoc.gr'],
                'web_pages' => ['https://www.uoc.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'University of Crete',
            ],
            [
                'state-province' => 'Thessaly',
                'country' => 'Greece',
                'domains' => ['uth.gr'],
                'web_pages' => ['https://www.uth.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'University of Thessaly',
            ],
            [
                'state-province' => 'Ionian Islands',
                'country' => 'Greece',
                'domains' => ['ionio.gr'],
                'web_pages' => ['https://www.ionio.gr/'],
                'alpha_two_code' => 'GR',
                'name' => 'Ionian University',
            ],
        ];

        $expectedrecordstotal = array_merge($page1, $page2);

        // Create a partial mock for the Handler class.
        $handlermock = $this->getMockBuilder(Handler::class)
            ->setConstructorArgs([$this->config])
            ->onlyMethods(['get_data_from_uri'])
            ->getMock();

        // Set up get_data_from_uri to return the next array in $expectedRecords on each call.
        $handlermock->expects($this->exactly(3))
            ->method('get_data_from_uri')
            ->will($this->onConsecutiveCalls(
                [
                    'page' => 1,
                    'limit' => 3,
                    'total' => 6,
                    'records' => $page1,
                ],
                [
                    'page' => 2,
                    'limit' => 3,
                    'total' => 6,
                    'records' => $page2,
                ],
                // We add an empty third page to test the loop exit condition.
                [
                    'page' => 3,
                    'limit' => 3,
                    'total' => 6,
                    'records' => [],
                ]
            ));

        $result = $handlermock->get_all_pages('', ['limit' => 3]);

        $this->assertEquals($expectedrecordstotal, $result);
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
