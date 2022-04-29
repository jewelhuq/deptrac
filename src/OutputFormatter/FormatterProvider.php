<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\OutputFormatter;

use Psr\Container\ContainerInterface;
use Qossmic\Deptrac\DependencyInjection\Exception\InvalidServiceInLocatorException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function array_keys;
use function get_debug_type;

final class FormatterProvider implements ContainerInterface
{
    private ServiceLocator $formatterLocator;

    public function __construct(ServiceLocator $formatterLocator)
    {
        $this->formatterLocator = $formatterLocator;
    }

    public function get(string $id): OutputFormatterInterface
    {
        $service = $this->formatterLocator->get($id);

        if (!$service instanceof OutputFormatterInterface) {
            throw InvalidServiceInLocatorException::invalidType($id, OutputFormatterInterface::class, get_debug_type($service));
        }

        return $service;
    }

    public function has(string $id): bool
    {
        return $this->formatterLocator->has($id);
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return string[]
     */
    public function getKnownFormatters(): array
    {
        return array_keys($this->formatterLocator->getProvidedServices());
    }
}
