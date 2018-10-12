<?php

namespace PHPModelGenerator\Tests\Objects;

use PHPModelGenerator\Exception\FileSystemException;
use PHPModelGenerator\Exception\InvalidArgumentException;
use PHPModelGenerator\Exception\RenderException;
use PHPModelGenerator\Exception\SchemaException;
use stdClass;

/**
 * Class StringPropertyTest
 *
 * @package PHPModelGenerator\Tests\Objects
 */
class StringPropertyTest extends AbstractPHPModelGeneratorTest
{
    /**
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testProvidedStringPropertyIsValid(): void
    {
        $className = $this->generateObjectFromFile('SimpleStringProperty.json');

        $object = new $className(['property' => 'Hello']);
        $this->assertEquals('Hello', $object->getProperty());
    }

    /**
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testNotProvidedOptionalStringPropertyIsValid(): void
    {
        $className = $this->generateObjectFromFile('SimpleStringProperty.json');

        $object = new $className([]);
        $this->assertTrue(is_callable([$object, 'getProperty']));
        $this->assertTrue(is_callable([$object, 'setProperty']));
        $this->assertNull($object->getProperty());
    }

    /**
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testProvidedOptionalStringPropertyIsValid(): void
    {
        $className = $this->generateObjectFromFile('SimpleStringProperty.json');

        $object = new $className(['property' => null]);
        $this->assertNull($object->getProperty());
    }

    /**
     * @dataProvider invalidPropertyTypeDataProvider
     *
     * @param $propertyValue
     *
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testInvalidPropertyTypeThrowsAnException($propertyValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid type for property');

        $className = $this->generateObjectFromFile('SimpleStringProperty.json');

        new $className(['property' => $propertyValue]);
    }

    public function invalidPropertyTypeDataProvider(): array
    {
        return [
            'int' => [1],
            'bool' => [true],
            'array' => [[]],
            'object' => [new stdClass()]
        ];
    }

    /**
     * @dataProvider stringInLengthValidationRangePassesDataProvider
     *
     * @param string $propertyValue
     *
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testStringInLengthValidationRangePasses(string $propertyValue): void
    {
        $className = $this->generateObjectFromFile('StringPropertyLengthValidation.json');

        $object = new $className(['property' => $propertyValue]);
        $this->assertEquals($propertyValue, $object->getProperty());
    }

    public function stringInLengthValidationRangePassesDataProvider(): array
    {
        return [
            'Lower limit' => ['11'],
            'Upper limit' => ['12345678']
        ];
    }

    /**
     * @dataProvider invalidStringLengthDataProvider
     *
     * @param string $propertyValue
     * @param string $exceptionMessage
     *
     * @throws FileSystemException
     * @throws RenderException
     * @throws SchemaException
     */
    public function testStringWithInvalidLengthThrowsAnException(string $propertyValue, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $className = $this->generateObjectFromFile('StringPropertyLengthValidation.json');

        new $className(['property' => $propertyValue]);
    }

    public function invalidStringLengthDataProvider(): array
    {
        return [
            'Too short string' => ['1', 'property must not be shorter than 2'],
            'Too long string' => ['Some Text', 'property must not be longer than 8']
        ];
    }
}