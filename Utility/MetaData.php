<?php


namespace MSDev\DoctrineFileMakerDriver\Utility;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Provider for doctrine metadata
 *
 */
class MetaData {

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * MetaData constructor
     */
    public function __construct() {
        $this->em = array_filter(debug_backtrace(), function($trace) {
            return isset($trace['object']) && $trace['object'] instanceof ObjectManager;
        });
    }

    /**
     * returns all namespaces of managed entities
     *
     * @return array
     */
    public function getEntityNamespaces() {
        return array_reduce($this->get(), function($carry, $item) {
            $carry[$item->table['name']] = $item->getName();
            return $carry;
        }, []);
    }

    /**
     * returns all entity meta data if existing
     *
     * @return array
     */
    public function get() {
        return empty($this->em) ? [] : array_pop($this->em)['object']->getMetaDataFactory()->getAllMetaData();
    }
}