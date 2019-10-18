<?php

declare(strict_types = 1);

namespace PHPModelGenerator\Model\SchemaDefinition;

use ArrayObject;
use PHPModelGenerator\Exception\SchemaException;
use PHPModelGenerator\Model\Schema;
use PHPModelGenerator\SchemaProcessor\SchemaProcessor;

/**
 * Class SchemaDefinitionDictionary
 *
 * @package PHPModelGenerator\Model\SchemaDefinition
 */
class SchemaDefinitionDictionary extends ArrayObject
{
    /** @var string */
    private $sourceDirectory;
    /** @var Schema[] */
    private $parsedExternalFileSchemas = [];

    /**
     * SchemaDefinitionDictionary constructor.
     *
     * @param string $sourceDirectory
     */
    public function __construct(string $sourceDirectory)
    {
        parent::__construct();

        $this->sourceDirectory = $sourceDirectory;
    }

    /**
     * Set up the definition directory for the schema
     *
     * @param array           $propertyData
     * @param SchemaProcessor $schemaProcessor
     * @param Schema          $schema
     */
    public function setUpDefinitionDictionary(
        array $propertyData,
        SchemaProcessor $schemaProcessor,
        Schema $schema
    ): void {
        // attach the root node to the definition dictionary
        $this->addDefinition('#', new SchemaDefinition($propertyData, $schemaProcessor, $schema));

        foreach ($propertyData as $key => $propertyEntry) {
            if (!is_array($propertyEntry)) {
                continue;
            }

            // add the root nodes of the schema to resolve path references
            $this->addDefinition($key, new SchemaDefinition($propertyEntry, $schemaProcessor, $schema));
        }

        $this->fetchDefinitionsById($propertyData, $schemaProcessor, $schema);
    }

    /**
     * Fetch all schema definitions with an ID for direct references
     *
     * @param array           $propertyData
     * @param SchemaProcessor $schemaProcessor
     * @param Schema          $schema
     */
    protected function fetchDefinitionsById(array $propertyData, SchemaProcessor $schemaProcessor, Schema $schema): void
    {
        if (isset($propertyData['$id'])) {
            $this->addDefinition($propertyData['$id'], new SchemaDefinition($propertyData, $schemaProcessor, $schema));
        }

        foreach ($propertyData as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->fetchDefinitionsById($item, $schemaProcessor, $schema);
        }
    }

    /**
     * Add a partial schema definition to the schema
     *
     * @param string $key
     * @param SchemaDefinition $definition
     *
     * @return $this
     */
    public function addDefinition(string $key, SchemaDefinition $definition): self
    {
        if (isset($this[$key])) {
            return $this;
        }

        $this[$key] = $definition;

        return $this;
    }

    /**
     * @param string          $key
     * @param SchemaProcessor $schemaProcessor
     * @param array           $path
     *
     * @return SchemaDefinition
     *
     * @throws SchemaException
     */
    public function getDefinition(string $key, SchemaProcessor $schemaProcessor, array &$path = []): ?SchemaDefinition
    {
        if (strpos($key, '#') === 0 && strpos($key, '/')) {
            $path = explode('/', $key);
            array_shift($path);
            $key  = array_shift($path);
        }

        if (!isset($this[$key])) {
            if (strstr($key, '#', true)) {
                [$jsonSchemaFile, $externalKey] = explode('#', $key);
            } else {
                $jsonSchemaFile = $key;
                $externalKey = '';
            }

            if (array_key_exists($jsonSchemaFile, $this->parsedExternalFileSchemas)) {
                return $this->parsedExternalFileSchemas[$jsonSchemaFile]->getSchemaDictionary()->getDefinition(
                    "#$externalKey",
                    $schemaProcessor,
                    $path
                );
            }

            return $jsonSchemaFile
                ? $this->parseExternalFile($jsonSchemaFile, "#$externalKey", $schemaProcessor, $path)
                : null;
        }

        return $this[$key] ?? null;
    }

    /**
     * @param string          $jsonSchemaFile
     * @param string          $externalKey
     * @param SchemaProcessor $schemaProcessor
     * @param array           $path
     *
     * @return SchemaDefinition|null
     *
     * @throws SchemaException
     */
    protected function parseExternalFile(
        string $jsonSchemaFile,
        string $externalKey,
        SchemaProcessor $schemaProcessor,
        array &$path
    ): ?SchemaDefinition {
        $jsonSchemaFilePath = filter_var($jsonSchemaFile, FILTER_VALIDATE_URL)
            ? $jsonSchemaFile
            : $this->sourceDirectory . '/' . $jsonSchemaFile;

        if (!filter_var($jsonSchemaFilePath, FILTER_VALIDATE_URL) && !is_file($jsonSchemaFilePath)) {
            throw new SchemaException("Reference to non existing JSON-Schema file $jsonSchemaFilePath");
        }

        $jsonSchema = file_get_contents($jsonSchemaFilePath);

        if (!$jsonSchema || !($jsonSchema = json_decode($jsonSchema, true))) {
            throw new SchemaException("Invalid JSON-Schema file $jsonSchemaFilePath");
        }

        // set up a dummy schema to fetch the definitions from the external file
        $schema = new Schema('', new self(dirname($jsonSchemaFilePath)));
        $schema->getSchemaDictionary()->setUpDefinitionDictionary($jsonSchema, $schemaProcessor, $schema);
        $this->parsedExternalFileSchemas[$jsonSchemaFile] = $schema;

        return $schema->getSchemaDictionary()->getDefinition($externalKey, $schemaProcessor, $path);
    }
}
