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
use GuzzleHttp\Client;
use \FileMaker;
use \FileMaker_Error;

class ContainerAccess
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
     * @var ScriptAccess
     */
    protected $script;

    public function __construct(Connection $conn, ScriptAccess $script)
    {
        /** @var FMConnection $fmcon */
        $this->con = $conn->getWrappedConnection();
        $this->fm = $this->con->getConnection();

        $this->script = $script;
    }



    /**
     * The command to extract externally stored document of conatainer field
     *
     * This function only works for containers using externally stored data,
     * if the conrtainer content is embeded, see below
     *
     * @param string $path	            - the URL path to the container
     * @return string $containerData	- return the conatainer data
     * @throws FMException
     */
    public function getExternalContainerContent($path) {
        $client = new Client();
        $url = $this->generateURL($path);

        try {
            $response = $client->get($url);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new FMException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * The command to extract internally stored document of conatainer field
     *
     * This function only works for non-external containers (i.e content embeded in a
     * container within the FM database.
     *
     * @param string $containerURL	    The FM URL for the container data
     * @return string $containerData    The container content
     * @throws FMException
     *
     */
    public function getContainerContent($containerURL)
    {
        if(empty($containerURL)) {
            throw new FMException("No container path specified");
        }

        $containerData = $this->fm->getContainerData($containerURL);

        if($this->con->isError($containerData)) {
            /** @var $containerData FileMaker_Error */
            throw new FMException($containerData->message, $containerData->code);
        }

        if(empty($containerData)) {
            throw new FMException("The container is empty");
        }

        return $containerData;
    }


    /**
     * Calls a FileMaker script to insert content into a container
     *
     * @param string $layout        The FM layout which the field to insert into is on
     * @param string $field         The name of the field
     * @param string $assetPath     A URL which the asset can be retrieved from
     *                              This either needs to be publically accessible, or include
     *                              the necesary credentials to access the asset using the
     *                              FileMaker InsertFromURL script step
     * @throws FMException
     *
     */
    public function insertIntoContainer($layout, $field, $assetPath)
    {
        $data = [
            'field' => $field,
            'asset' => $assetPath,
        ];

        try {
            $this->script->performScript($layout, 'ImportToContainer', json_encode($data));
        } catch(\Exception $e) {
            throw new FMException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Generate a request URL for container data from Doctrine parameters
     * and the passed FM path
     *
     * @param string $path
     * @return string
     */
    private function generateURL($path)
    {
        $params = $this->con->getParameters();

        $host = $params['host'];
        $proto = (443 == $params['port'] ? 'https' : 'http').'://';
        $cred = empty($params['user'])
            ? ''
            : ((empty($params['password']) ? $params['user'] : $params['user'].':'.$params['password']).'@');

        return $proto.$cred.$host.$path;
    }
}