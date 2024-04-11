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
use GuzzleHttp\Client;
use Exception;

/**
 * Tests for the Config class.
 *
 * @package    local_guzzletest
 * @category   test
 * @author     Yannis Maragos <maragos.y@wideservices.gr>
 * @copyright  2024 onwards WIDE Services {@link https://www.wideservices.gr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_guzzletest\httputils\Config
 */
class config_test extends \advanced_testcase {
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
     * Test that the 'get_instance' method returns a singleton instance.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Config::get_instance
     */
    public function test_get_instance_returns_singleton(): void {
        $config1 = Config::get_instance($this->baseuri, $this->mockclient);
        $config2 = Config::get_instance($this->baseuri, $this->mockclient);

        $this->assertSame($config1, $config2);
    }

    /**
     * Tests the 'get_setting' method with a key that exists.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Config::get_setting
     */
    public function test_get_setting_key_exists() {
        $this->assertEquals(20, $this->config->get_setting('SETTING_TIMEOUT'));
    }

    /**
     * Tests the 'get_setting' method with an invalid key.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Config::get_setting
     */
    public function test_get_setting_invalid_key() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Setting INVALID_KEY not found');

        $this->config->get_setting('INVALID_KEY');
    }

    /**
     * Test that the 'get_base_uri' method returns the correct base URI.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Config::get_base_uri
     */
    public function get_base_uri() {
        $this->assertEquals($this->baseuri, $this->config->get_base_uri());
    }

    /**
     * Test that the 'get_http_client' method returns an object equal to the mock client.
     *
     * @return void
     * @covers \local_guzzletest\httputils\Config::get_base_uri
     */
    public function test_get_http_client() {
        $this->assertEquals($this->mockclient, $this->config->get_http_client());
    }

    /**
     * Clean up after all tests have finished.
     *
     * @return void
     */
    protected function tearDown(): void {
    }
}
