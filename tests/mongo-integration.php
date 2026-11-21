<?php declare(strict_types=1);

/**
 * @package     Triangle Session Component Tests
 * @link        https://github.com/Triangle-org/Session
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <triangle@localzet.com>
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use MongoDB\Client;
use Triangle\Session\Handlers\MongoSessionHandler;

function expectMongoIntegration(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$uri = getenv('MONGODB_URI');
if ($uri === false || $uri === '') {
    throw new RuntimeException('MONGODB_URI must point to a MongoDB integration-test service.');
}

if (!extension_loaded('mongodb')) {
    throw new RuntimeException('The mongodb PHP extension is required for this integration test.');
}

$databaseName = 'triangle_session_test_' . bin2hex(random_bytes(8));
$collectionName = 'sessions';
$sessionId = 'sessionmongo' . bin2hex(random_bytes(8));
$client = new Client($uri, ['serverSelectionTimeoutMS' => 5000]);
$collection = $client->selectCollection($databaseName, $collectionName);
$handler = new MongoSessionHandler([
    'url' => $uri,
    'database' => $databaseName,
    'collection' => $collectionName,
    'options' => ['serverSelectionTimeoutMS' => 5000],
]);

try {
    $payload = [
        'user_id' => 41,
        'preferences' => ['theme' => 'dark'],
    ];

    expectMongoIntegration($handler->open('', 'PHPSESSID'), 'Mongo session handler should open.');
    expectMongoIntegration($handler->write($sessionId, serialize($payload)), 'Mongo session handler should write valid serialized arrays.');

    $document = $collection->findOne(['_id' => $sessionId]);
    expectMongoIntegration($document !== null, 'MongoDB should persist the session document.');
    expectMongoIntegration($document->user_id === 41, 'MongoDB should preserve scalar session values.');
    expectMongoIntegration($document->preferences->theme === 'dark', 'MongoDB should preserve nested session values.');
    expectMongoIntegration($document->updated_at instanceof MongoDB\BSON\UTCDateTime, 'MongoDB should persist the update timestamp as BSON UTCDateTime.');

    $restored = unserialize($handler->read($sessionId), ['allowed_classes' => false]);
    expectMongoIntegration($restored === $payload, 'Mongo session data should survive a BSON write/read round trip.');

    expectMongoIntegration(!$handler->write($sessionId, 'not a serialized array'), 'Malformed session payloads should be rejected.');
    $unchanged = $collection->findOne(['_id' => $sessionId]);
    expectMongoIntegration($unchanged->user_id === 41 && $unchanged->preferences->theme === 'dark', 'Rejected payloads must not overwrite stored session data.');

    expectMongoIntegration($handler->destroy($sessionId), 'Mongo session handler should destroy a session.');
    expectMongoIntegration($collection->findOne(['_id' => $sessionId]) === null, 'Destroyed sessions should be removed from MongoDB.');
} finally {
    $client->dropDatabase($databaseName);
}

echo "SESSION_MONGODB_INTEGRATION=ok\n";
