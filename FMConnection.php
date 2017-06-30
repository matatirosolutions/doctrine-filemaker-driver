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

class FMConnection extends AbstractConnection
{

    /**
     * @var FileMaker
     */
    private $connection = null;

    /**
     * @var FMStatement
     */
    private $statement = null;

    /**
     * @var bool
     */
    private $transactionOpen = false;

    /**
     * @var array
     */
    protected $params;

    public function __construct(array $params, FMDriver $driver)
    {
        $this->params = $params;

        $hostspec = $params['host'] . empty($params['port']) ?: ':'.$params['port'];
        $this->connection = new FileMaker($params['dbname'], $hostspec, $params['user'], $params['password']);

        parent::__construct($params, $driver);
    }

    public function rollBack()
    {
        // this method must exist, but rollback isn't possible so nothing is implemented
    }

    public function prepare($prepareString)
    {
        $this->statement = new FMStatement($prepareString, $this);
        $this->statement->setFetchMode($this->defaultFetchMode);

        return $this->statement;
    }

    public function beginTransaction()
    {
        $this->transactionOpen = true;
        return true;
    }

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

    public function commit()
    {
        $this->statement->performQueries();
        $this->transactionOpen = false;

        return true;
    }


    public function isTransactionOpen()
    {
        return $this->transactionOpen;
    }


    public function lastInsertId($name = null)
    {
        return $this->statement->extractID();
    }

    public function getServerVersion()
    {
        return $this->connection->getAPIVersion();
    }

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
//
//    public function requiresQueryForServerVersion()
//    {
//        return true;
//    }
}