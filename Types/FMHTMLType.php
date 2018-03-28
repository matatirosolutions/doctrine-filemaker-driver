<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 24/08/2017
 * Time: 17:00
 */

namespace MSDev\DoctrineFileMakerDriver\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 *
 */
class FMHTMLType extends Type
{
    protected $name = 'fmhtml';

    public function getName()
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return html_entity_decode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}