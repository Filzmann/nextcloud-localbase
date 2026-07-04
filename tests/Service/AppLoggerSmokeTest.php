<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IUserSession::class)) {
        eval('namespace OCP; interface IUserSession { public function getUser(); }');
    }
    if (!interface_exists(\Psr\Log\LoggerInterface::class)) {
        eval('namespace Psr\Log; interface LoggerInterface { public function emergency($message, array $context = array()); public function alert($message, array $context = array()); public function critical($message, array $context = array()); public function error($message, array $context = array()); public function warning($message, array $context = array()); public function notice($message, array $context = array()); public function info($message, array $context = array()); public function debug($message, array $context = array()); public function log($level, $message, array $context = array()); }');
    }

    require __DIR__ . '/../../lib/Service/AppLogger.php';

    use OCA\LocalBase\Service\AppLogger;
    use OCP\IUserSession;
    use Psr\Log\LoggerInterface;

    $records = [];

    $logger = new class($records) implements LoggerInterface {
        public function __construct(private array &$records) {
        }

        public function emergency($message, array $context = array()): void {}
        public function alert($message, array $context = array()): void {}
        public function critical($message, array $context = array()): void {}
        public function warning($message, array $context = array()): void {}
        public function notice($message, array $context = array()): void {}
        public function debug($message, array $context = array()): void {}
        public function log($level, $message, array $context = array()): void {}

        public function error($message, array $context = array()): void {
            $this->records[] = ['level' => 'error', 'message' => $message, 'context' => $context];
        }

        public function info($message, array $context = array()): void {
            $this->records[] = ['level' => 'info', 'message' => $message, 'context' => $context];
        }
    };

    $session = new class implements IUserSession {
        public function getUser(): object {
            return new class {
                public function getUID(): string {
                    return 'simon';
                }
            };
        }
    };

    $appLogger = new AppLogger($logger, $session);
    $appLogger->error('demo', 'Demo', 'save', new \RuntimeException('Kaputt'), [
        'id' => 7,
        'dry_run' => false,
        'empty' => null,
        'object' => new \stdClass(),
    ]);
    $appLogger->info('demo', 'background_job', ['sent' => 3, 'data' => ['private']]);

    if ($records[0]['message'] !== 'Demo error during save') {
        throw new \RuntimeException('Error message should include app label and action.');
    }
    if (($records[0]['context']['app'] ?? '') !== 'demo' || ($records[0]['context']['user_id'] ?? '') !== 'simon') {
        throw new \RuntimeException('Error context should include app and user id.');
    }
    if (($records[0]['context']['exception_class'] ?? '') !== \RuntimeException::class || ($records[0]['context']['exception_message'] ?? '') !== 'Kaputt') {
        throw new \RuntimeException('Error context should include exception metadata.');
    }
    if (($records[0]['context']['dry_run'] ?? null) !== false || !array_key_exists('empty', $records[0]['context'])) {
        throw new \RuntimeException('Scalar false and null context values should be preserved.');
    }
    if (array_key_exists('object', $records[0]['context'])) {
        throw new \RuntimeException('Object context values must not be logged.');
    }
    if (array_key_exists('data', $records[1]['context'])) {
        throw new \RuntimeException('Array context values must not be logged.');
    }

    $anonymousRecords = [];
    $anonymousLogger = new class($anonymousRecords) implements LoggerInterface {
        public function __construct(private array &$records) {
        }

        public function emergency($message, array $context = array()): void {}
        public function alert($message, array $context = array()): void {}
        public function critical($message, array $context = array()): void {}
        public function warning($message, array $context = array()): void {}
        public function notice($message, array $context = array()): void {}
        public function info($message, array $context = array()): void {}
        public function debug($message, array $context = array()): void {}
        public function log($level, $message, array $context = array()): void {}

        public function error($message, array $context = array()): void {
            $this->records[] = ['message' => $message, 'context' => $context];
        }
    };
    $anonymousSession = new class implements IUserSession {
        public function getUser(): ?object {
            return null;
        }
    };
    (new AppLogger($anonymousLogger, $anonymousSession))->error('demo', 'Demo', 'delete', new \RuntimeException('Kaputt'));

    if (array_key_exists('user_id', $anonymousRecords[0]['context'])) {
        throw new \RuntimeException('Anonymous error context should not include a user id.');
    }

    echo 'AppLogger smoke tests passed' . PHP_EOL;
}
