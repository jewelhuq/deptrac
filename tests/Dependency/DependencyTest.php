<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Dependency;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\Dependency\Dependency;

final class DependencyTest extends TestCase
{
    public function testGetSet(): void
    {
        $dependency = new Dependency(
            ClassLikeToken::fromFQCN('a'),
            ClassLikeToken::fromFQCN('b'),
            FileOccurrence::fromFilepath('/foo.php', 23)
        );
        self::assertSame('a', $dependency->getDepender()->toString());
        self::assertSame('/foo.php', $dependency->getFileOccurrence()->getFilepath());
        self::assertSame(23, $dependency->getFileOccurrence()->getLine());
        self::assertSame('b', $dependency->getDependent()->toString());
    }
}
