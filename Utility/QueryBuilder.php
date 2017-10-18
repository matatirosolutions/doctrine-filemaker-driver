<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 03/04/2017
 * Time: 20:31
 */

namespace MSDev\DoctrineFileMakerDriver\Utility;

use Doctrine\ORM\Mapping\ClassMetadata;
use MSDev\DoctrineFileMakerDriver\FMConnection;
use MSDev\DoctrineFileMakerDriver\Exceptions\FMException;
use Symfony\Component\Intl\Exception\NotImplementedException;

class QueryBuilder
{
    /** @var
     * FileMaker
     */
    private $fmp;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var array
     */
    private $query;


    public function __construct(FMConnection $conn)
    {
        $this->fmp = $conn->getConnection();
    }


    public function getOperation()
    {
        return $this->operation;
    }


    public function getQueryFromRequest(array $tokens, string $statement, array $params) {
        $this->operation = strtolower(array_keys($tokens)[0]);

        switch($this->operation) {
            case 'select':
                return $this->generateFindCommand($tokens, $params);
            case 'update':
                return $this->generateUpdateCommand($tokens, $statement, $params);
            case 'insert':
                return $this->generateInsertCommand($tokens, $params);
            case 'delete':
                return $this->generateDeleteCommand($tokens);
        }

        throw new NotImplementedException('Unknown request type');
    }


    private function generateFindCommand($tokens, $params) {
        $layout = $this->getLayout($tokens);

        if (empty($this->query['WHERE'])) {
            $cmd = $this->fmp->newFindAllCommand($layout);
        } else {
            $cmd = $this->generateWhere($params, $layout);
        }

        // Sort
        if(array_key_exists('ORDER', $this->query)) {
            foreach($this->query['ORDER'] as $k => $rule) {
                $dir = 'ASC' == $rule['direction'] ? FILEMAKER_SORT_ASCEND : FILEMAKER_SORT_DESCEND;
                $cmd->addSortRule($rule['no_quotes']['parts'][1], $k+1, $dir);
            }
        }

        // Limit
        if('subquery' == $tokens['FROM'][0]['expr_type']) {
            $skip = (int)$tokens['WHERE'][2]['base_expr'] - 1;
            $max = isset($tokens['WHERE'][6]['base_expr']) ? (int)$tokens['WHERE'][6]['base_expr'] - $skip : 1;
            $cmd->setRange($skip, $max);
        }

        return $cmd;
    }


    private function generateUpdateCommand($tokens, $statement, $params) {
        $layout = $this->getLayout($tokens);
        $recID = $this->getRecordID($tokens, $layout);

        $data = $matches = [];
        $count = 1;

        preg_match('/ SET (.*) WHERE /', $statement, $matches);
        $pairs = explode(',', $matches[1]);

        foreach($pairs as $up) {
            $details = explode('=', $up);
            $field = trim(array_shift($details));
            if($field) {
                $data[$field] = $params[$count];
                $count++;
            }
        }

        return $this->fmp->newEditCommand($layout, $recID, $data);
    }


    private function generateDeleteCommand($tokens)
    {
        $layout = $this->getLayout($tokens);
        $recID = $this->getRecordID($tokens, $layout);

        return $this->fmp->newDeleteCommand($layout, $recID);
    }


    private function getRecordID($tokens, $layout)
    {
        $cmd = $this->fmp->newFindCommand($layout);
        $value = '==';
        for($i=2; $i<count($tokens['WHERE']); $i++) {
            $value .= $tokens['WHERE'][$i]['base_expr'];
        }
        $cmd->addFindCriterion($tokens['WHERE'][0]['base_expr'], $value);

        $res = $cmd->execute();

        if(is_a($res, 'FileMaker_Error')) {
            /** @var FileMaker_Error $res */
            throw new FMException($res->getMessage(), $res->code);
        }

        return $res->getFirstRecord()->getRecordId();
    }


    private function generateInsertCommand($tokens, $params)
    {
        $layout = $this->getLayout($tokens);
        $list = substr($tokens['INSERT'][2]['base_expr'], 1, -1);
        $fields = explode(',', $list);

        // need to know which is the Id column
        $idColumn = $this->getIdColumn($tokens, new MetaData());

        $data = [];
        foreach($fields as $c => $f) {
            $field = trim($f);
            if('rec_id' === $field || ($idColumn === $field && empty($params[$c+1]))) {
                continue;
            }
            $data[$field] = $params[$c+1];
        }

        return $this->fmp->newAddCommand($layout, $data);
    }


    public function getLayout(array $tokens) {
        $this->query = $tokens;
        if (empty($tokens['FROM']) && empty($tokens['INSERT']) && empty($tokens['UPDATE'])) {
            throw new \Exception('Unknown layout');
        }

        switch($this->operation) {
            case 'insert':
                return $tokens['INSERT'][1]['no_quotes']['parts'][0];
            case 'update':
                return $tokens['UPDATE'][0]['no_quotes']['parts'][0];
            default:
                if('subquery' == $tokens['FROM'][0]['expr_type']) {
                    $this->query = $tokens['FROM'][0]['sub_tree']['FROM'][0]['sub_tree'];
                    return $tokens['FROM'][0]['sub_tree']['FROM'][0]['sub_tree']['FROM'][0]['no_quotes']['parts'][0];
                }
                return $tokens['FROM'][0]['no_quotes']['parts'][0];
        }
    }

    private function generateWhere($params, $layout)
    {

        $cols = $this->selectColumns($this->query);
        $cmd = $this->fmp->newFindCommand($layout);
        $pc = 1;

        for($c = 0; $c<count($this->query['WHERE']); $c++) {
            $query = $this->query['WHERE'][$c];

            if(array_key_exists($query['base_expr'], $cols)) {
                // if the comparison operator is '=' then double up to '=='
                $comp = $this->query['WHERE'][$c+1]['base_expr'];
                $op = '=' == $comp ? '==' :  $comp;

                $field = $query['no_quotes']['parts'][1];
                $value = $op.$params[$pc];

                $cmd->addFindCriterion($field, $value);
                $pc++;
            }
        }
        return $cmd;
    }


    private function selectColumns($tokens)
    {
        $cols = [];
        foreach($tokens['SELECT'] as $column) {
            if(isset($column['no_quotes'])) {
                $cols[$column['base_expr']] = $column['no_quotes']['parts'][1];
                continue;
            }

            if(isset($column['sub_tree'])) {
                $field = [];
                foreach($column['sub_tree'] as $sub) {
                    //$parts =
                    $field[] = end($sub['no_quotes']['parts']);
                }
                $cols[$column['base_expr']] = implode(' ', $field);
            }
        }

        return $cols;
    }

    /**
     * returns the column of the id
     *
     * @param  array    $tokens
     * @param  MetaData $metaData
     * @return string
     *
     */
    public function getIdColumn(array $tokens, MetaData $metaData)
    {
        $table = $this->getLayout($tokens);
        $meta = array_filter($metaData->get(), function ($meta) use ($table) {
            /** @var ClassMetadata $meta */
            return $meta->getTableName() === $table;
        });

        $idColumns = !empty($meta) ? end($meta)->getIdentifierColumnNames() : [];

        return !empty($idColumns) ? end($idColumns) : 'id';
    }

}