<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 17/02/2017
 * Time: 16:42
 */

namespace MSDev\DoctrineFileMakerDriver;

use Doctrine\DBAL\Connection as AbstractConnection;
use \FileMaker;
use MSDev\DoctrineFileMakerDriver\FMDriver;
use MSDev\DoctrineFileMakerDriver\FMStatement;
use Symfony\Component\Intl\Exception\NotImplementedException;

class FMConnection extends AbstractConnection
{

    /**
     * @var FileMaker
     */
    private $connection = null;

    private $statement = null;

    protected $params;

    public function __construct(array $params, FMDriver $driver)
    {
        $this->params = $params;

        $hostspec = $params['host'] . empty($params['port']) ?: ':'.$params['port'];
        $this->connection = new FileMaker($params['dbname'], $hostspec, $params['user'], $params['password']);

        parent::__construct($params, $driver);
    }

//    public function rollBack()
//    {
//        // this method must exist, but rollback isn't possible so nothing is implemented
//    }

    public function prepare($prepareString)
    {

        $this->statement = new FMStatement($prepareString, $this);
        $this->statement->setFetchMode($this->defaultFetchMode);

        return $this->statement;
    }

//    public function beginTransaction()
//    {
//        // this method must exist, but transactions aren't possible so nothing is implemented
//    }


    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];

        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }
//
//    public function commit()
//    {
//        // TODO: Implement commit() method.
//    }
//
//    public function lastInsertId($name = null)
//    {
//        // TODO: Implement lastInsertId() method.
//    }
//
//    public function quote($input, $type = \PDO::PARAM_STR)
//    {
//        // TODO: Implement quote() method.
//    }
//
//    public function errorInfo()
//    {
//        // TODO: Implement errorInfo() method.
//    }
//
//    public function exec($statement)
//    {
//        // TODO: Implement exec() method.
//    }
//
//    public function errorCode()
//    {
//        // TODO: Implement errorCode() method.
//    }
//
    public function getServerVersion()
    {
        return $this->connection->getAPIVersion();
    }
//
//    public function requiresQueryForServerVersion()
//    {
//        return true;
//    }

    /**
     * @return FileMaker
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function isError($in) {
        return is_a($in, 'FileMaker_Error');
    }

    public function getParameters()
    {
        return $this->params;
    }

}