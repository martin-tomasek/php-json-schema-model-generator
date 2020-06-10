<?php

declare(strict_types=1);

namespace PHPModelGenerator\SchemaProvider;

use PHPModelGenerator\Exception\FileSystemException;
use PHPModelGenerator\Exception\SchemaException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * Class RecursiveDirectoryProvider
 *
 * @package PHPModelGenerator\SchemaProvider
 */
class RecursiveDirectoryProvider implements SchemaProviderInterface
{
    /** @var string */
    private $sourceDirectory;

    /**
     * RecursiveDirectoryProvider constructor.
     *
     * @param string $sourceDirectory
     *
     * @throws FileSystemException
     */
    public function __construct(string $sourceDirectory)
    {
        if (!is_dir($sourceDirectory)) {
            throw new FileSystemException("Source directory '$sourceDirectory' doesn't exist");
        }

        $this->sourceDirectory = $sourceDirectory;
    }

    /**
     * @inheritDoc
     *
     * @throws SchemaException
     */
    public function getSchemas(): iterable
    {
        $directory = new RecursiveDirectoryIterator($this->sourceDirectory);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach (new RegexIterator($iterator, '/^.+\.json$/i', RecursiveRegexIterator::GET_MATCH) as $file) {
            $jsonSchema = file_get_contents($file[0]);

            if (!$jsonSchema || !($decodedJsonSchema = json_decode($jsonSchema, true))) {
                throw new SchemaException("Invalid JSON-Schema file {$file[0]}");
            }

            yield [$file[0], $decodedJsonSchema];
        }
    }

    /**
     * @inheritDoc
     */
    public function getBaseDirectory(): string
    {
        return $this->sourceDirectory;
    }
}
