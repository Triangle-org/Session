<?php declare(strict_types=1);

/**
 * @package     Triangle Session Component Tests
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * @license     AGPL-3.0-or-later
 */

namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

namespace MongoDB {
    if (!class_exists(Client::class)) {
        class Client
        {
        }

        define(__NAMESPACE__ . '\\SESSION_TEST_STUBS', true);
    }

    if (!class_exists(Collection::class)) {
        class Collection
        {
            public static ?array $lastReplacement = null;
            public static ?array $lastOptions = null;
            public static mixed $lastDocument = null;

            public function findOne(array $filter, array $options = []): mixed
            {
                self::$lastOptions = $options;
                return self::$lastDocument;
            }

            public function replaceOne(array $filter, array $replacement, array $options = []): void
            {
                self::$lastReplacement = $replacement;
            }

            public function updateOne(array $filter, array $update): void
            {
            }
        }
    }
}

namespace MongoDB\BSON {
    if (!class_exists(UTCDateTime::class)) {
        class UTCDateTime
        {
        }
    }
}

namespace {
    use localzet\Server;
    use localzet\Server\Protocols\Http\Session as ServerSession;
    use localzet\Server\Protocols\Http\Session\SessionHandlerInterface;
    use Triangle\Engine\BootstrapInterface;
    use Triangle\Session\Bootstrap;
    use Triangle\Session\Handlers\FileSessionHandler;
    use Triangle\Session\Handlers\MongoSessionHandler;
    use Triangle\Session\Handlers\RedisClusterSessionHandler;
    use Triangle\Session\Handlers\RedisSessionHandler;

    final class SessionCompatibilityProbe
    {
        public static bool $woken = false;

        public function __wakeup(): void
        {
            self::$woken = true;
        }
    }

    function expect(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    expect(is_subclass_of(Bootstrap::class, BootstrapInterface::class), 'Session bootstrap must implement the Engine bootstrap contract.');
    Bootstrap::start(new Server());

    foreach ([FileSessionHandler::class, MongoSessionHandler::class, RedisSessionHandler::class, RedisClusterSessionHandler::class] as $handlerClass) {
        expect(is_subclass_of($handlerClass, SessionHandlerInterface::class), "$handlerClass must implement the Server session handler contract.");
    }

    $sessionId = 'sessioncompat1234567890';
    $savePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'triangle-session-' . bin2hex(random_bytes(6));

    try {
        ServerSession::handlerClass(FileSessionHandler::class, ['save_path' => $savePath]);

        $session = new ServerSession($sessionId);
        $session->put(['user_id' => 41, 'preferences' => ['theme' => 'dark']]);
        $session->save();

        $restored = new ServerSession($sessionId);
        expect($restored->get('user_id') === 41, 'Scalar session values must survive a storage round trip.');
        expect($restored->get('preferences') === ['theme' => 'dark'], 'Nested session arrays must survive a storage round trip.');

        SessionCompatibilityProbe::$woken = false;
        ServerSession::handler()->write($sessionId, serialize(['payload' => new SessionCompatibilityProbe()]));

        $untrusted = new ServerSession($sessionId);
        expect(!SessionCompatibilityProbe::$woken, 'Session payload objects must not execute wakeup hooks when restored.');
        expect($untrusted->get('payload') instanceof __PHP_Incomplete_Class, 'Objects in restored session data must remain incomplete values.');
    } finally {
        ServerSession::handler()->destroy($sessionId);
        @rmdir($savePath);
    }

    if (defined('MongoDB\\SESSION_TEST_STUBS')) {
        $reflection = new ReflectionClass(MongoSessionHandler::class);
        $mongoHandler = $reflection->newInstanceWithoutConstructor();
        $collection = new \MongoDB\Collection();

        $property = $reflection->getProperty('collection');
        $property->setAccessible(true);
        $property->setValue($mongoHandler, $collection);

        \MongoDB\Collection::$lastDocument = [
            '_id' => $sessionId,
            'updated_at' => new \MongoDB\BSON\UTCDateTime(),
            'user_id' => 41,
            'preferences' => ['theme' => 'dark'],
        ];
        $storedData = unserialize($mongoHandler->read($sessionId), ['allowed_classes' => false]);
        expect($storedData === ['user_id' => 41, 'preferences' => ['theme' => 'dark']], 'Mongo session reads must preserve nested arrays without storage metadata.');
        expect(\MongoDB\Collection::$lastOptions['typeMap'] === ['root' => 'array', 'document' => 'array', 'array' => 'array'], 'Mongo session reads must restore BSON documents and arrays as PHP arrays.');

        SessionCompatibilityProbe::$woken = false;
        $validPayload = serialize(['name' => 'safe', 'payload' => new SessionCompatibilityProbe()]);
        expect($mongoHandler->write($sessionId, $validPayload), 'Mongo session writes should accept serialized arrays.');
        expect(!SessionCompatibilityProbe::$woken, 'Mongo session writes must not instantiate objects from serialized session data.');
        expect(\MongoDB\Collection::$lastReplacement['payload'] instanceof __PHP_Incomplete_Class, 'Mongo session writes must preserve object payloads as incomplete values.');

        $previousReplacement = \MongoDB\Collection::$lastReplacement;
        expect(!$mongoHandler->write($sessionId, 'not a serialized array'), 'Malformed Mongo session payloads must be rejected.');
        expect(\MongoDB\Collection::$lastReplacement === $previousReplacement, 'Rejected Mongo payloads must not mutate storage.');
    }

    echo "SESSION_ENGINE_COMPATIBILITY=ok\n";
}
