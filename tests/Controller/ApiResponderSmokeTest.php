<?php

declare(strict_types=1);

namespace {
    if (!class_exists(\OCP\AppFramework\Http::class)) {
        eval('namespace OCP\AppFramework; class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; public const STATUS_INTERNAL_SERVER_ERROR = 500; }');
    }
    if (!class_exists(\OCP\AppFramework\Http\DataResponse::class)) {
        eval('namespace OCP\AppFramework\Http; class DataResponse { public function __construct(private mixed $data = [], private int $status = 200) {} public function getData(): mixed { return $this->data; } public function getStatus(): int { return $this->status; } }');
    }

    require __DIR__ . '/../../lib/Controller/ApiResponder.php';

    use OCA\LocalBase\Controller\ApiResponder;
    use OCP\AppFramework\Http;

    $responder = new ApiResponder();
    $logged = [];
    $logger = static function (string $action, \Throwable $e, array $context) use (&$logged): void {
        $logged[] = [$action, get_class($e), $context];
    };

    $success = $responder->respond(static fn(): array => ['ok' => true], $logger, 'save');
    if ($success->getData() !== ['ok' => true] || $success->getStatus() !== 200) {
        throw new \RuntimeException('Successful responses should wrap callback data.');
    }

    $explicitResponse = new \OCP\AppFramework\Http\DataResponse(['direct' => true], 202);
    $preservedResponse = $responder->respond(static fn(): \OCP\AppFramework\Http\DataResponse => $explicitResponse, $logger, 'direct');
    if ($preservedResponse !== $explicitResponse || $preservedResponse->getStatus() !== 202) {
        throw new \RuntimeException('Explicit DataResponse callback results should be preserved.');
    }

    $badRequest = $responder->respond(static function (): array {
        throw new \InvalidArgumentException('Ungueltig');
    }, $logger, 'validate');
    if ($badRequest->getStatus() !== Http::STATUS_BAD_REQUEST || $badRequest->getData()['message'] !== 'Ungueltig') {
        throw new \RuntimeException('InvalidArgumentException should become HTTP 400.');
    }
    if ($logged !== []) {
        throw new \RuntimeException('Client errors should not be logged as server errors.');
    }

    $forbidden = $responder->respond(static function (): array {
        throw new \DomainException('Verboten');
    }, $logger, 'authorize');
    if ($forbidden->getStatus() !== Http::STATUS_FORBIDDEN || $forbidden->getData()['message'] !== 'Verboten') {
        throw new \RuntimeException('DomainException should become HTTP 403.');
    }
    if ($logged !== []) {
        throw new \RuntimeException('Forbidden errors should not be logged as server errors.');
    }

    $serverError = $responder->respond(static function (): array {
        throw new \RuntimeException('Intern');
    }, $logger, 'store', ['id' => 7], 'Bitte spaeter erneut versuchen.');
    if ($serverError->getStatus() !== Http::STATUS_INTERNAL_SERVER_ERROR || $serverError->getData()['message'] !== 'Bitte spaeter erneut versuchen.') {
        throw new \RuntimeException('Unexpected server error response.');
    }
    if ($logged !== [['store', \RuntimeException::class, ['id' => 7]]]) {
        throw new \RuntimeException('Unexpected logged error context.');
    }

    $defaultServerError = $responder->respond(static function (): array {
        throw new \RuntimeException('Intern');
    }, $logger, 'store_default');
    if ($defaultServerError->getData()['message'] !== 'Die Aktion konnte nicht ausgeführt werden. Details stehen im Nextcloud-Log.') {
        throw new \RuntimeException('Der gemeinsame Standardfehler muss echte Umlaute verwenden.');
    }

    $directError = $responder->error('Direkte Meldung', Http::STATUS_BAD_REQUEST);
    if ($directError->getData() !== ['ok' => false, 'message' => 'Direkte Meldung'] || $directError->getStatus() !== Http::STATUS_BAD_REQUEST) {
        throw new \RuntimeException('Direct error responses should keep the shared API shape.');
    }

    echo 'ApiResponder smoke tests passed' . PHP_EOL;
}
