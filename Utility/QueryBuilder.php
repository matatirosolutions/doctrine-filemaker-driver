<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 03/04/2017
 * Time: 20:31
 */

namespace MSDev\DoctrineFileMakerDriver\Utility;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use MSDev\DoctrineFileMakerDriver\Utility\MetaData;
use MSDev\DoctrineFileMakerDriver\FMConnection;
use Symfony\Component\Intl\Exception\NotImplementedException;
use \FileMaker;

class QueryBuilder
{

    /** @var  FileMaker */
    private $fmp;

    private $operation;

    private $query;

    public function __construct(FMConnection $conn)
    {
        $this->fmp = $conn->getConnection();
    }

    public function getQueryFromRequest(array $tokens, array $params) {
        $this->operation = strtolower(array_keys($tokens)[0]);

        switch($this->operation) {
            case 'select':
                return $this->generateFindCommand($tokens, $params);
            case 'update':
                return $this->generateUpdateCommand($tokens);
            case 'insert':
                return $this->generateInsertCommand($tokens, $params);
        }

        throw new NotImplementedException('Unknown request type');
    }



    private function generateFindCommand($tokens, $params) { dump($tokens); dump($params); //die();
        $layout = $this->getLayout($tokens);

//dump($layout); die();
        if (empty($this->query['WHERE'])) {
            $cmd = $this->fmp->newFindAllCommand($layout);
        } else {
            $cmd = $this->generateWhere($params, $layout);
//dump($cmd);
        }

//        $cmd->addFindCriterion($tokens['WHERE'][0]['no_quotes']['parts'][1], $tokens['WHERE'][1]['base_expr'].str_replace("~", "-", $tokens['WHERE'][2]['base_expr']));
//dump($this->query);
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
            $max = (int)$tokens['WHERE'][6]['base_expr'] - $skip;
            $cmd->setRange($skip, $max);
        }

dump($cmd); //die();
        return $cmd;
    }

    private function generateUpdateCommand($tokens) {
        $layout = $this->getLayout($tokens);
        $recID = $tokens['WHERE'][2]['base_expr'];

        $data = [];
        foreach($tokens['SET'] as $up) {
            $details = explode('=', $up['base_expr']);
            $field = trim(array_shift($details));
            $data[$field] = trim(implode('=', $details));
        }

        return $this->fmp->newEditCommand($layout, $recID, $data);
    }

    private function generateInsertCommand($tokens, $params)
    {
        $layout = $this->getLayout($tokens);
        $list = substr($tokens['INSERT'][2]['base_expr'], 1, -1);
        $fields = explode(',', $list);

        $data = [];
        foreach($fields as $c => $f) {
            if('id' === $f) {
                continue;
            }
            $data[trim($f)] = $params[$c+1];
        }

        return $this->fmp->newAddCommand($layout, $data);
    }

    private function getLayout(array $tokens) {
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
        $tokens = $this->query;
        $cols = $this->selectColumns($tokens);
//dump($cols);
        $cmd = $this->fmp->newCompoundFindCommand($layout);
        $pc = 1;

        for($c = 0; $c<count($tokens['WHERE']); $c++) {
            $query = $tokens['WHERE'][$c];
//dump($query);
            if(array_key_exists($query['base_expr'], $cols)) {
                $field = $query['no_quotes']['parts'][1];
                $comp = $tokens['WHERE'][$c+1]['base_expr'];
                $value = $params[$pc];

                $find = $this->fmp->newFindRequest($layout);
                $find->addFindCriterion($field, $comp.$value);
                $cmd->add($pc, $find);

                $pc++;
                dump($find);
            }
        }
//dump($cmd);
        return $cmd;
    }


    private function selectColumns($tokens)
    {
        $cols = [];
        foreach($tokens['SELECT'] as $column) {
            $cols[$column['base_expr']] = $column['no_quotes']['parts'][1];
        }

        return $cols;
    }
















    private function create($value) {
        if (empty($value)) return null;

        $unquoted = preg_replace('/\"|\\\'|\`$/', '', preg_replace('/^\"|\\\'|\`/', '', $value));
        if (!is_numeric($unquoted))                   return $unquoted;
        if ((string) intval($unquoted) === $unquoted) return intval($unquoted);

        return floatval($unquoted);
    }


    private function getWhere($tokens) {
            $idAlias = $this->alias($tokens);
            return array_reduce($tokens['WHERE'], function($carry, $token) use ($tokens, $idAlias) {
                if (!is_int($carry)) return $carry;
                if ($token['expr_type'] === 'colref' && $token['base_expr'] === $idAlias) return str_replace('~', '-', $tokens['WHERE'][$carry+2]['base_expr']);
                if (!isset($tokens[$carry+1])) return '';
            }, 0);
        }


    /**
     * Returns the id alias
     *
     * @param  array $tokens
     * @return string
     *
     */
    private function alias(array $tokens) {
        $column     = $this->column($tokens, new MetaData());
        $tableAlias = $this->tableAlias($tokens);

        return empty($tableAlias) ? $column : $tableAlias . '.' . $column;
    }

    /**
     * returns the column of the id
     *
     * @param  array    $tokens
     * @param  MetaData $metaData
     * @return string
     *
     */
    private function column(array $tokens, MetaData $metaData) {
        $table = $this->getLayout($tokens);
        $meta  = array_filter($metaData->get(), function($meta) use ($table) {
            return $meta->getTableName() === $table;
        });

        $idColumns  = !empty($meta) ? end($meta)->getIdentifierColumnNames() : [];

        return !empty($idColumns) ? end($idColumns) : 'id';
    }

    /**
     * Returns the table's alias
     *
     * @param  array  $tokens
     * @return null|string
     */
    private function tableAlias(array $tokens) {
        switch($this->operation) {
            case 'insert':
                return null;
            case 'update':
                return $tokens['UPDATE'][0]['alias']['name'];
            default:
                return $tokens['FROM'][0]['alias']['name'];
        }
    }
}