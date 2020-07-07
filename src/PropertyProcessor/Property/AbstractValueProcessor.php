<?php

declare(strict_types = 1);

namespace PHPModelGenerator\PropertyProcessor\Property;

use PHPModelGenerator\Exception\SchemaException;
use PHPModelGenerator\Model\Property\Property;
use PHPModelGenerator\Model\Property\PropertyInterface;
use PHPModelGenerator\Model\Schema;
use PHPModelGenerator\PropertyProcessor\Decorator\TypeHint\TypeHintDecorator;
use PHPModelGenerator\PropertyProcessor\Filter\FilterProcessor;
use PHPModelGenerator\PropertyProcessor\PropertyMetaDataCollection;
use PHPModelGenerator\SchemaProcessor\SchemaProcessor;
use ReflectionException;

/**
 * Class AbstractScalarValueProcessor
 *
 * @package PHPModelGenerator\PropertyProcessor\Property
 */
abstract class AbstractValueProcessor extends AbstractPropertyProcessor
{
    private $type = '';

    /**
     * AbstractValueProcessor constructor.
     *
     * @param PropertyMetaDataCollection $propertyMetaDataCollection
     * @param SchemaProcessor            $schemaProcessor
     * @param Schema                     $schema
     * @param string                     $type
     */
    public function __construct(
        PropertyMetaDataCollection $propertyMetaDataCollection,
        SchemaProcessor $schemaProcessor,
        Schema $schema,
        string $type = ''
    ) {
        parent::__construct($propertyMetaDataCollection, $schemaProcessor, $schema);
        $this->type = $type;
    }

    /**
     * @inheritdoc
     *
     * @throws ReflectionException
     * @throws SchemaException
     */
    public function process(string $propertyName, array $propertyData): PropertyInterface
    {
        $property = (new Property($propertyName, $this->type, $propertyData['description'] ?? ''))
            ->setRequired($this->propertyMetaDataCollection->isAttributeRequired($propertyName))
            ->setReadOnly(
                (isset($propertyData['readOnly']) && $propertyData['readOnly'] === true) ||
                $this->schemaProcessor->getGeneratorConfiguration()->isImmutable()
            );

        if ($this->schemaProcessor->getGeneratorConfiguration()->isImplicitNullAllowed() && !$property->isRequired()) {
            $property->addTypeHintDecorator(new TypeHintDecorator(['null']));
        }

        $this->generateValidators($property, $propertyData);

        if (isset($propertyData['filter'])) {
            (new FilterProcessor())->process(
                $property,
                $propertyData['filter'],
                $this->schemaProcessor->getGeneratorConfiguration(),
                $this->schema
            );
        }

        return $property;
    }
}
