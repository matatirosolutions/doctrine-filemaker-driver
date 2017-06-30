<?php
/*
 * This Generator is identical to the Doctrine equivilent except
 * that it's not converting all IDs to integers, since the most common
 * usecase for this in FM is to use Get(UUID) to generate the ID field
 */

namespace MSDev\DoctrineFileMakerDriver;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;

/**
 * Id generator that obtains IDs from special "identity" columns. These are columns
 * that automatically get a database-generated, auto-incremented identifier on INSERT.
 * This generator obtains the last insert id after such an insert.
 */
class FMIdentityGenerator extends AbstractIdGenerator
{
    /**
     * The name of the sequence to pass to lastInsertId(), if any.
     *
     * @var string
     */
    private $sequenceName;

    /**
     * Constructor.
     *
     * @param string|null $sequenceName The name of the sequence to pass to lastInsertId()
     *                                  to obtain the last generated identifier within the current
     *                                  database session/connection, if any.
     */
    public function __construct($sequenceName = null)
    {
        $this->sequenceName = $sequenceName;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return $em->getConnection()->lastInsertId($this->sequenceName);
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator()
    {
        return true;
    }
}
