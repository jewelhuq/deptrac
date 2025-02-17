<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Analyser;

use Psr\EventDispatcher\EventDispatcherInterface;
use Qossmic\Deptrac\Contract\Analyser\AnalysisResult;
use Qossmic\Deptrac\Contract\Analyser\PostProcessEvent;
use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\Contract\Layer\InvalidLayerDefinitionException;
use Qossmic\Deptrac\Contract\Result\Warning;
use Qossmic\Deptrac\Core\Ast\AstException;
use Qossmic\Deptrac\Core\Ast\AstMapExtractor;
use Qossmic\Deptrac\Core\Dependency\DependencyResolver;
use Qossmic\Deptrac\Core\Dependency\InvalidEmitterConfigurationException;
use Qossmic\Deptrac\Core\Dependency\TokenResolver;
use Qossmic\Deptrac\Core\Dependency\UnrecognizedTokenException;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;

use function count;

class DependencyLayersAnalyser
{
    public function __construct(
        private readonly AstMapExtractor $astMapExtractor,
        private readonly DependencyResolver $dependencyResolver,
        private readonly TokenResolver $tokenResolver,
        private readonly LayerResolverInterface $layerResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws AnalyserException
     */
    public function analyse(): AnalysisResult
    {
        try {
            $astMap = $this->astMapExtractor->extract();

            $dependencies = $this->dependencyResolver->resolve($astMap);

            $result = new AnalysisResult();
            $warnings = [];

            foreach ($dependencies->getDependenciesAndInheritDependencies() as $dependency) {
                $depender = $dependency->getDepender();
                $dependerRef = $this->tokenResolver->resolve($depender, $astMap);
                $dependerLayers = array_keys($this->layerResolver->getLayersForReference($dependerRef));

                if (!isset($warnings[$depender->toString()]) && count($dependerLayers) > 1) {
                    $warnings[$depender->toString()] =
                        Warning::tokenIsInMoreThanOneLayer($depender->toString(), $dependerLayers);
                }

                $dependent = $dependency->getDependent();
                $dependentRef = $this->tokenResolver->resolve($dependent, $astMap);
                $dependentLayers = $this->layerResolver->getLayersForReference($dependentRef);

                foreach ($dependerLayers as $dependentLayer) {
                    $event = new ProcessEvent(
                        $dependency, $dependerRef, $dependentLayer, $dependentRef, $dependentLayers, $result
                    );
                    $this->eventDispatcher->dispatch($event);

                    $result = $event->getResult();
                }
            }

            $result->addWarnings($warnings);

            $event = new PostProcessEvent($result);
            $this->eventDispatcher->dispatch($event);

            return $event->getResult();
        } catch (InvalidEmitterConfigurationException $e) {
            throw AnalyserException::invalidEmitterConfiguration($e);
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
