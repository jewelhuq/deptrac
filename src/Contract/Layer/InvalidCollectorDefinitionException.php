<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Layer;

use Psr\Container\ContainerExceptionInterface;
use Qossmic\Deptrac\Contract\ExceptionInterface;
use RuntimeException;

use function implode;

final class InvalidCollectorDefinitionException extends RuntimeException implements ExceptionInterface
{
    public static function missingType(): self
    {
        return new self('Could not resolve collector definition because of missing "type" field.');
    }

    /**
     * @param string[] $supportedTypes
     */
    public static function unsupportedType(string $collectorType, array $supportedTypes, ?ContainerExceptionInterface $previous): self
    {
        return new self(
            sprintf('Could not find a collector for type "%s". Supported types: "%s".', $collectorType, implode('", "', $supportedTypes)),
            0,
            $previous
        );
    }

    public static function unsupportedClass(string $id, mixed $collector): self
    {
        $message = sprintf(
            'Type "%s" is not valid collector (expected "%s", but is "%s").',
            $id,
            CollectorInterface::class,
            get_debug_type($collector)
        );

        return new self($message);
    }

    public static function invalidCollectorConfiguration(string $message): self
    {
        return new self($message);
    }
}
