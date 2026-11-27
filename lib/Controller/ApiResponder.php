<?php

declare(strict_types=1);

namespace OCA\LocalBase\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use Throwable;

class ApiResponder {
    public function __construct(private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }
    public function respond(
        callable $callback,
        callable $logError,
        string $action,
        array $context = [],
        ?string $serverErrorMessage = null
    ): DataResponse {
        $serverErrorMessage ??= $this->t('Die Aktion konnte nicht ausgeführt werden. Details stehen im Nextcloud-Log.');
        try {
            $response = $callback();

            return $response instanceof DataResponse ? $response : new DataResponse($response);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), Http::STATUS_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), Http::STATUS_FORBIDDEN);
        } catch (Throwable $e) {
            $logError($action, $e, $context);

            return $this->error($serverErrorMessage, Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    public function error(string $message, int $status): DataResponse {
        return new DataResponse([
            'ok' => false,
            'message' => $message,
        ], $status);
    }
}
