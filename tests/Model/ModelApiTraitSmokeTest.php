<?php

declare(strict_types=1);

namespace {
    require __DIR__ . '/../../lib/Model/ModelApiTrait.php';

    use OCA\LocalBase\Model\ModelApiTrait;

    function assertModelSame(mixed $expected, mixed $actual, string $message): void {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    function assertModelThrows(callable $callback, string $exceptionClass, string $message): void {
        try {
            $callback();
        } catch (Throwable $exception) {
            if ($exception instanceof $exceptionClass) {
                return;
            }

            throw new RuntimeException($message . ' Threw ' . get_class($exception) . ' instead.');
        }

        throw new RuntimeException($message . ' No exception was thrown.');
    }

    class LocalBaseTraitConstructorModel {
        use ModelApiTrait;

        public function __construct(public array $data) {
        }

        public function toArray(): array {
            return $this->data;
        }
    }

    class LocalBaseTraitArrayModel {
        use ModelApiTrait;

        public function __construct(public array $data) {
        }

        protected static function fromArray(array $data): self {
            return new self(['source' => 'array'] + $data);
        }

        protected static function fromRow(array $data): self {
            return new self(['source' => 'row'] + $data);
        }

        public function toArray(): array {
            return $this->data;
        }
    }

    class LocalBaseTraitRowModel {
        use ModelApiTrait;

        public function __construct(public array $data) {
        }

        protected static function fromRow(array $data): self {
            return new self(['source' => 'row'] + $data);
        }

        public function toArray(): array {
            return $this->data;
        }
    }

    class LocalBaseTraitDefaultModel {
        use ModelApiTrait;
    }

    $constructorModel = LocalBaseTraitConstructorModel::get(['id' => 7]);
    assertModelSame(['id' => 7], $constructorModel->toArray(), 'Constructor fallback should hydrate array data.');
    assertModelSame($constructorModel, LocalBaseTraitConstructorModel::get($constructorModel), 'Existing model instances should be returned unchanged.');
    assertModelSame(null, LocalBaseTraitConstructorModel::get(null), 'Null should hydrate to null.');

    $objectData = (object)['id' => 8, 'title' => 'Test'];
    assertModelSame(['id' => 8, 'title' => 'Test'], LocalBaseTraitConstructorModel::get($objectData)->toArray(), 'Objects should be converted to arrays.');

    $all = LocalBaseTraitConstructorModel::get_all([
        ['id' => 1],
        null,
        (object)['id' => 2],
    ]);
    assertModelSame(2, count($all), 'get_all should skip null payloads.');
    assertModelSame([1, 2], [$all[0]->toArray()['id'], $all[1]->toArray()['id']], 'get_all should keep item order.');

    assertModelSame(['source' => 'array', 'id' => 9], LocalBaseTraitArrayModel::get(['id' => 9])->toArray(), 'fromArray should be preferred over fromRow.');
    assertModelSame(['source' => 'row', 'id' => 10], LocalBaseTraitRowModel::get(['id' => 10])->toArray(), 'fromRow should be used as fallback.');
    assertModelSame(['id' => 7], $constructorModel->to_array(), 'to_array should delegate to toArray.');

    assertModelThrows(
        static fn() => LocalBaseTraitConstructorModel::get('invalid'),
        InvalidArgumentException::class,
        'Scalar payloads should be rejected.'
    );
    assertModelThrows(
        static fn() => (new LocalBaseTraitDefaultModel())->toArray(),
        RuntimeException::class,
        'Models without toArray should fail loudly.'
    );
    assertModelThrows(
        static fn() => (new LocalBaseTraitDefaultModel())->save(),
        RuntimeException::class,
        'Non-persistable models should fail loudly on save.'
    );

    echo 'ModelApiTrait smoke tests passed' . PHP_EOL;
}
