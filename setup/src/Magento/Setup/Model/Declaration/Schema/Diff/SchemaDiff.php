<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Model\Declaration\Schema\Diff;

use Magento\Setup\Model\Declaration\Schema\Dto\Schema;
use Magento\Setup\Model\Declaration\Schema\OperationsExecutor;

/**
 * Aggregation root of all diffs.
 * Loop through all tables and find difference between them.
 *
 * If table exists only in XML -> then we need to create table.
 * If table exists in both version -> then we need to go deeper and inspect each element.
 * If table exists only in db -> then we need to remove this table.
 */
class SchemaDiff
{
    /**
     * @var TableDiff
     */
    private $tableDiff;

    /**
     * @var DiffManager
     */
    private $diffManager;

    /**
     * @var DiffFactory
     */
    private $diffFactory;

    /**
     * @var OperationsExecutor
     */
    private $operationsExecutor;

    /**
     * Constructor.
     *
     * @param DiffManager $diffManager
     * @param TableDiff $tableDiff
     * @param DiffFactory $diffFactory
     * @param OperationsExecutor $operationsExecutor
     */
    public function __construct(
        DiffManager $diffManager,
        TableDiff $tableDiff,
        DiffFactory $diffFactory,
        OperationsExecutor $operationsExecutor
    ) {
        $this->tableDiff = $tableDiff;
        $this->diffManager = $diffManager;
        $this->diffFactory = $diffFactory;
        $this->operationsExecutor = $operationsExecutor;
    }

    /**
     * Create diff.
     *
     * @param Schema $schema
     * @param Schema $generatedSchema
     * @return Diff
     */
    public function diff(
        Schema $schema,
        Schema $generatedSchema
    ) {
        $generatedTables = $generatedSchema->getTables();
        $diff = $this->diffFactory->create(
            [
                'tableIndexes' => array_flip(array_keys($schema->getTables())),
                'destructiveOperations' => $this->operationsExecutor->getDestructiveOperations()
            ]
        );

        foreach ($schema->getTables() as $name => $table) {
            if ($this->diffManager->shouldBeCreated($generatedTables, $table)) {
                $diff = $this->diffManager->registerCreation($diff, $table);
            } else {
                $diff = $this->tableDiff->diff($table, $generatedTables[$name], $diff);
            }

            unset($generatedTables[$name]);
        }
        //Removal process
        if ($this->diffManager->shouldBeRemoved($generatedTables)) {
            $diff = $this->diffManager->registerRemoval($diff, $generatedTables);
        }

        return $diff;
    }
}
