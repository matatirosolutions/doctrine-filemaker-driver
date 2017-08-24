<?php


namespace MSDev\DoctrineFileMakerDriver\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 *
 *
 */
class FMDateTimeType extends Type
{
    protected $name = 'fmdatetime';

    public function getName()
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $this->name;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        if(is_int($value) && $value > 0) {
            return date('m/d/Y H:i:s', $value);
        }

        if($value instanceof \DateTime) {
            return $value->format('m/d/Y H:i:s');
        }


        throw ConversionException::conversionFailed(var_export($value, true), $this->name);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }

        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $value);
        if($date && $date->format('m/d/Y H:i:s') === $value) {
            return $date;
        }

        throw ConversionException::conversionFailed(var_export($value, true), $this->name);
    }
}