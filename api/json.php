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
 * Fetches page data from a given JSON file URL based on the page number and limit.
 *
 * @package    local_guzzletest
 * @copyright  2024 Yannis Maragos <maragos.y@wideservices.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once('../../../config.php');

$error = false;

// Simulate response error.
if ($error) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => [
            'code' => 500,
            'message' => 'Internal Server Error: Something went wrong.',
        ],
    ]);
    exit;
}

$page = optional_param('page', 1, PARAM_INT);
$limit = optional_param('limit', null, PARAM_INT);
$offset = ($page - 1) * $limit;

// Fetch the content from the url.
$jsonurl = $CFG->wwwroot . '/local/guzzletest/api/db.json';
$json = file_get_contents($jsonurl);
$data = json_decode($json, true);

// Extract records for the specified page.
$records = array_slice($data, $offset, $limit);

$response = json_encode([
    'page' => $page,
    'limit' => $limit,
    'total' => count($data),
    'records' => $records
]);

header('Content-Type: application/json');
echo $response;
