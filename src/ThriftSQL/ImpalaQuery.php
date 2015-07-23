<?php

namespace ThriftSQL;

/**
 * Class ImpalaQuery
 * @package ThriftSQL
 *
 * @property \ThriftSQL\ImpalaServiceClient $_client
 */
class ImpalaQuery implements \ThriftSQLQuery
{

    private $_handle;
    private $_client;
    private $_ready;

    public function __construct($queryStr, $client)
    {
        $queryCleaner = new \ThriftSQL\Utils\QueryCleaner();

        $this->_client = $client;
        $this->_ready = false;
        $this->_handle = $this->_client->query(new \ThriftSQL\Query(array(
            'query' => $queryCleaner->clean($queryStr),
        )));
    }

    public function wait()
    {
        $sleeper = new \ThriftSQL\Utils\Sleeper();

        // Wait for results
        $sleeper->reset();
        do {

            $slept = $sleeper->sleep()->getSleptSecs();

            if ($slept > 18000) { // 5 Hours
                // TODO: Actually kill the query then throw exception.
                throw new \ThriftSQL\Exception('Impala Query Killed!');
            }

            $state = $this
                ->_client
                ->get_state($this->_handle);

            if ($this->_isOperationFinished($state)) {
                break;
            }

            if ($this->_isOperationRunning($state)) {
                continue;
            }

            // Query in error state
            throw new \ThriftSQL\Exception(
                'Query is in an error state: ' . \ThriftSQL\QueryState::$__names[$state]
            );

        } while (true);

        $this->_ready = true;
    }

    public function fetch($maxRows)
    {
        if (!$this->_ready) {
            throw new \ThriftSQL\Exception("Query is not ready. Call `->wait()` before `->fetch()`");
        }
        try {
            $sleeper = new \ThriftSQL\Utils\Sleeper();
            $sleeper->reset();

            do {
                $response = $this->_client->fetch($this->_handle, false, $maxRows);
                if ($response->ready) {
                    break;
                }
                $slept = $sleeper->sleep()->getSleptSecs();

                if ($slept > 60) { // 1 minute
                    throw new \ThriftSQL\Exception('Impala Query took too long to fetch!');
                }

            } while (true);

            return $this->_parseResponse($response);
        } catch (Exception $e) {
            throw new \ThriftSQL\Exception($e->getMessage());
        }
    }

    private function _parseResponse($response)
    {
        $result = array();
        $resultMetadata = $this->_client->get_results_metadata($this->_handle);
        foreach ($response->data as $row => $rawValues) {
            $values = explode("\t", $rawValues, count($response->columns));

            foreach ($response->columns as $col => $dataType) {

                $fieldName = $resultMetadata->schema->fieldSchemas[$col]->name;

                switch ($dataType) {
                    case 'int':
                    case 'bigint':
                    case 'smallint':
                    case 'tinyint':
                        $result[$row][$fieldName] = intval($values[$col]);
                        break;

                    case 'decimal':
                    case 'double':
                    case 'float':
                    case 'real':
                        $result[$row][$fieldName] = floatval($values[$col]);
                        break;

                    case 'boolean':
                        $result[$row][$fieldName] = ('true' === $values[$col]);
                        break;

                    case 'char':
                    case 'string':
                    case 'varchar':
                    case 'timestamp':
                    default:
                        $result[$row][$fieldName] = $values[$col];
                        break;
                }
            }
        }
        return $result;
    }

    private function _isOperationFinished($state)
    {
        return (\ThriftSQL\QueryState::FINISHED == $state);
    }

    private function _isOperationRunning($state)
    {
        return in_array(
            $state,
            array(
                \ThriftSQL\QueryState::CREATED,     // 0
                \ThriftSQL\QueryState::INITIALIZED, // 1
                \ThriftSQL\QueryState::COMPILED,    // 2
                \ThriftSQL\QueryState::RUNNING,     // 3
            )
        );
    }
}