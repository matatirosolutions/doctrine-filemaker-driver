<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 10/04/2017
 * Time: 15:10
 */

namespace MSDev\DoctrineFileMakerDriver\Utility;

use Doctrine\DBAL\Connection;
use MSDev\DoctrineFileMakerDriver\Exceptions\FMException;
use MSDev\DoctrineFileMakerDriver\FMConnection;
use \FileMaker;

class ScriptAccess
{

    /**
     * @var FMConnection
     */
    protected $con;

    /**
     * @var FileMaker
     */
    protected $fm;

    /**
     * ScriptAccess constructor.
     * @param Connection $conn
     */
    function __construct(Connection $conn)
    {
        $this->con = $conn->getWrappedConnection();
        $this->fm = $this->con->getConnection();
    }


    public function performScript($layout, $script, $params = null)
    {
        dump($layout);
        dump($script);
        dump($params);
        $cmd = $this->fm->newPerformScriptCommand($layout, $script, $params);
        $res = $cmd->execute();

        if($this->con->isError($res)) {
            switch($res->code) {
                default:
                    throw new FMException($res->message, $res->code);
            }
        }
    }
}