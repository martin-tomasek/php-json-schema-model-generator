<?php

declare(strict_types = 1);

namespace PHPModelGenerator\Model\Validator;

use PHPModelGenerator\Exception\ComposedValue\ConditionalException;
use PHPModelGenerator\Model\Property\CompositionPropertyDecorator;
use PHPModelGenerator\Model\Property\PropertyInterface;
use PHPModelGenerator\PropertyProcessor\ComposedValue\IfProcessor;

/**
 * Class ConditionalPropertyValidator
 *
 * @package PHPModelGenerator\Model\Validator
 */
class ConditionalPropertyValidator extends AbstractComposedPropertyValidator
{
    /**
     * ConditionalPropertyValidator constructor.
     *
     * @param PropertyInterface              $property
     * @param CompositionPropertyDecorator[] $composedProperties
     * @param array                          $validatorVariables
     */
    public function __construct(
        PropertyInterface $property,
        array $composedProperties,
        array $validatorVariables
    ) {
        parent::__construct(
            DIRECTORY_SEPARATOR . 'Validator' . DIRECTORY_SEPARATOR . 'ConditionalComposedItem.phptpl',
            $validatorVariables,
            ConditionalException::class,
            [$property->getName()]
        );

        $this->composedProcessor = IfProcessor::class;
        $this->composedProperties = $composedProperties;
    }
}
