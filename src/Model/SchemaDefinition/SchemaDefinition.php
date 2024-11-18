<?php

declare(strict_types = 1);

namespace PHPModelGenerator\Model\SchemaDefinition;

use PHPModelGenerator\Exception\PHPModelGeneratorException;
use PHPModelGenerator\Exception\SchemaException;
use PHPModelGenerator\Model\Property\PropertyInterface;
use PHPModelGenerator\Model\Property\PropertyProxy;
use PHPModelGenerator\Model\Schema;
use PHPModelGenerator\PropertyProcessor\PropertyMetaDataCollection;
use PHPModelGenerator\PropertyProcessor\PropertyFactory;
use PHPModelGenerator\PropertyProcessor\PropertyProcessorFactory;
use PHPModelGenerator\SchemaProcessor\SchemaProcessor;

/**
 * Class SchemaDefinition
 *
 * Hold a definition from a schema
 *
 * @package PHPModelGenerator\Model
 */
class SchemaDefinition
{
    protected ResolvedDefinitionsCollection $resolvedPaths;
    /** @var array */
    protected $unresolvedProxies = [];

    /**
     * SchemaDefinition constructor.
     */
    public function __construct(
        protected JsonSchema $source,
        protected SchemaProcessor $schemaProcessor,
        protected Schema $schema,
    ) {
        $this->resolvedPaths = new ResolvedDefinitionsCollection();
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * Resolve a reference
     *
     * @throws PHPModelGeneratorException
     * @throws SchemaException
     */
    public function resolveReference(
        string $propertyName,
        array $path,
        PropertyMetaDataCollection $propertyMetaDataCollection,
    ): PropertyInterface {
        $jsonSchema = $this->source->getJson();
        $originalPath = $path;

        while ($segment = array_shift($path)) {
            if (!isset($jsonSchema[$segment])) {
                throw new SchemaException("Unresolved path segment $segment in file {$this->source->getFile()}");
            }

            $jsonSchema = $jsonSchema[$segment];
        }

        // if the properties point to the same definition and share identical metadata the generated property can be
        // recycled. Otherwise, a new property must be generated as diverging metadata lead to different validators.
        $key = implode('-', [...$originalPath, $propertyMetaDataCollection->getHash($propertyName)]);

        if (!$this->resolvedPaths->offsetExists($key)) {
            // create a dummy entry for the path first. If the path is used recursive the recursive usages will point
            // to the currently created property
            $this->resolvedPaths->offsetSet($key, null);

            try {
                $property =  (new PropertyFactory(new PropertyProcessorFactory()))
                    ->create(
                        $propertyMetaDataCollection,
                        $this->schemaProcessor,
                        $this->schema,
                        $propertyName,
                        $this->source->withJson($jsonSchema),
                    );
                $this->resolvedPaths->offsetSet($key, $property);

                /** @var PropertyProxy $proxy */
                foreach ($this->unresolvedProxies[$key] ?? [] as $proxy) {
                    $proxy->resolve();
                }

                unset($this->unresolvedProxies[$key]);

                return $property;
            } catch (PHPModelGeneratorException $exception) {
                $this->resolvedPaths->offsetUnset($key);
                throw $exception;
            }
        }

        $proxy = new PropertyProxy($propertyName, $this->source, $this->resolvedPaths, $key);
        $this->unresolvedProxies[$key][] = $proxy;

        if ($this->resolvedPaths->offsetGet($key)) {
            $proxy->resolve();
        }

        return $proxy;
    }
}
