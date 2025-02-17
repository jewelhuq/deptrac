<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Analyser;

use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\Contract\Layer\InvalidLayerDefinitionException;
use Qossmic\Deptrac\Core\Ast\AstException;
use Qossmic\Deptrac\Core\Ast\AstMapExtractor;
use Qossmic\Deptrac\Core\Dependency\TokenResolver;
use Qossmic\Deptrac\Core\Dependency\UnrecognizedTokenException;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;
use Qossmic\Deptrac\Supportive\DependencyInjection\EmitterType;

use function array_values;
use function natcasesort;

class UnassignedTokenAnalyser
{
    /**
     * @var array<TokenType>
     */
    private readonly array $tokenTypes;

    /**
     * @param array{types: array<string>} $config
     */
    public function __construct(
        private readonly AstMapExtractor $astMapExtractor,
        private readonly TokenResolver $tokenResolver,
        private readonly LayerResolverInterface $layerResolver,
        array $config
    ) {
        $this->tokenTypes = array_filter(
            array_map(
                static fn (string $emitterType): ?TokenType => TokenType::tryFromEmitterType(EmitterType::from($emitterType)),
                $config['types']
            )
        );
    }

    /**
     * @return string[]
     *
     * @throws AnalyserException
     */
    public function findUnassignedTokens(): array
    {
        try {
            $astMap = $this->astMapExtractor->extract();
            $unassignedTokens = [];

            if (in_array(TokenType::CLASS_LIKE, $this->tokenTypes, true)) {
                foreach ($astMap->getClassLikeReferences() as $classReference) {
                    $token = $this->tokenResolver->resolve($classReference->getToken(), $astMap);
                    if ([] === $this->layerResolver->getLayersForReference($token)) {
                        $unassignedTokens[] = $classReference->getToken()->toString();
                    }
                }
            }

            if (in_array(TokenType::FUNCTION, $this->tokenTypes, true)) {
                foreach ($astMap->getFunctionLikeReferences() as $functionReference) {
                    $token = $this->tokenResolver->resolve($functionReference->getToken(), $astMap);
                    if ([] === $this->layerResolver->getLayersForReference($token)) {
                        $unassignedTokens[] = $functionReference->getToken()->toString();
                    }
                }
            }

            if (in_array(TokenType::FILE, $this->tokenTypes, true)) {
                foreach ($astMap->getFileReferences() as $fileReference) {
                    $token = $this->tokenResolver->resolve($fileReference->getToken(), $astMap);
                    if ([] === $this->layerResolver->getLayersForReference($token)) {
                        $unassignedTokens[] = $fileReference->getToken()->toString();
                    }
                }
            }

            natcasesort($unassignedTokens);

            return array_values($unassignedTokens);
        } catch (UnrecognizedTokenException $e) {
            throw AnalyserException::unrecognizedToken($e);
        } catch (InvalidLayerDefinitionException $e) {
            throw AnalyserException::invalidLayerDefinition($e);
        } catch (InvalidCollectorDefinitionException $e) {
            throw AnalyserException::invalidCollectorDefinition($e);
        } catch (AstException $e) {
            throw AnalyserException::failedAstParsing($e);
        } catch (CouldNotParseFileException $e) {
            throw AnalyserException::couldNotParseFile($e);
        }
    }
}
