<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database;

use ArrayAccess\WP\Libraries\Core\Util\Filter;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use function array_merge;
use function is_array;
use function is_string;
use function method_exists;
use function stripos;

/* ----------------- Example of usage -----------------
 *
$databaseSchemaArray = [
    'members' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'unsigned' => true,
            'nullable' => false,
            'autoincrement' => true,
            'primary' => true,
        ],
        'user_id' => [
            'type' => 'bigint',
            'length' => 20,
            'unsigned' => true,
            'nullable' => false,
            'default' => 0,
            'indexes' => [
                'user_id' => [
                    'columns' => ['user_id'],
                ],
            ],
            'foreignKeys' => [
                'user_id' => [
                    'table' => 'users',
                    'columns' => ['user_id'],
                    'references' => ['id'],
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                ],
            ],
        ],
        'email' => [
            'type' => 'varchar',
            'length' => 255,
            'nullable' => false,
            'default' => '',
        ],
        'password' => [
            'type' => 'varchar',
            'length' => 255,
            'nullable' => false,
            'default' => '',
        ],
        'created_at' => [
            'type' => 'datetime',
            'nullable' => false,
            'default' => '0000-00-00 00:00:00',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'nullable' => false,
            'default' => '0000-00-00 00:00:00',
        ],
    ],
];
*/
/**
 * Database comparator.
 * Compare database schema with given schema.
 */
class DatabaseComparator
{
    public function __construct(
        protected Connection $connection
    ) {
    }

    /**
     * Get connection.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get schema diff from given schema.
     *
     * @param Schema $schema Schema to compare, use the local / user defined schema.
     * @return SchemaDiff|SchemaException|Exception
     */
    public function getSchemaDiff(Schema $schema): SchemaDiff|SchemaException|Exception
    {
        try {
            // get compilation database schema from doctrine with current given schema
            $schemaManager = $this
                ->getConnection()
                ->createSchemaManager();
            $comparator = $schemaManager->createComparator();
            $clonedSchema = clone $schemaManager->introspectSchema();
            foreach ($schemaManager->listTableNames() as $table) {
                // if table not exists in current schema, drop it
                if (!$schema->hasTable($table)) {
                    $clonedSchema->dropTable($table);
                }
            }

            // compare the schema
            return $comparator->compareSchemas(
                $clonedSchema,
                $schema
            );
        } catch (SchemaException|Exception $e) {
            return $e;
        }
    }

    /**
     * Create schema from tables.
     *
     * @param Table ...$tables Tables to create schema.
     * @return Schema|SchemaException Schema instance if success, SchemaException if failed.
     */
    public function createFromTables(Table ...$tables): Schema|SchemaException
    {
        try {
            return new Schema($tables);
        } catch (SchemaException $e) {
            return $e;
        }
    }

    /**
     * @param string $tableName Table name.
     * @param array<array{
     *     name: string,
     *     type: string,
     *     options?: array<string, mixed>,
     *     indexes: array<array{
     *          columns: array<string>,
     *          name: string,
     *          flags?: array<string, mixed>
     *     }>,
     * }> $columnDefinitions Column definitions.
     * @param bool $usePrefix Use prefix or not. Default is true.
     * @return Table|Exception Table instance if success, Exception if failed.
     */
    public function createTableFromDefinition(
        string $tableName,
        array $columnDefinitions,
        bool $usePrefix = true
    ): Table|Exception {
        try {
            $tablePrefix = $this->getConnection()->getPrefix();
            if ($usePrefix) {
                // check if it was not start with prefix append it
                // case-insensitive
                if (stripos($tableName, $tablePrefix) !== 0) {
                    $tableName = $tablePrefix . $tableName;
                }
            }
            $table = new Table($tableName);
            $primaryKeys = [];
            $tableIndex  = [];
            $tableUnique  = [];
            foreach ($columnDefinitions as $columnName => $columnDefinition) {
                $columnName = $columnDefinition['column_name']??$columnName;
                $columnName = !is_string($columnName) ? ($columnDefinition['name']??null) : $columnName;
                if (!Filter::shouldStrings($columnName, $columnDefinition['type']??null)) {
                    throw new Exception(
                        'Column name and type should be string.'
                    );
                }
                // remove name and column_name from column definition
                unset($columnDefinition['name'], $columnDefinition['column_name']);
                // type
                $type = $columnDefinition['type'];
                // indexes
                $indexes = $columnDefinition['indexes']??null;
                // foreign keys
                $foreignKeys = $columnDefinition['foreignKeys']??null;
                $foreignKeys = $foreignKeys??$columnDefinition['foreign_keys']??null;
                // options, merge from definition and options
                $options = $columnDefinition['options']??[];
                $options = !is_array($options) ? [] : $options;
                // remove indexes, foreignKeys, options from column definition
                $options = array_merge($columnDefinition, $options);
                if (isset($options['nullable'])) {
                    $options['notnull'] = !$options['nullable'];
                }
                if (($options['primary']??false) === true) {
                    $primaryKeys[] = $columnName;
                } elseif (($options['primary_key']??false) === true) {
                    $primaryKeys[] = $columnName;
                }
                if (($options['unique']??false) === true) {
                    $tableUnique[] = $columnName;
                }
                if (($options['index']??false) === true) {
                    $tableIndex[] = $columnName;
                }
                unset(
                    $options['indexes'],
                    $options['foreignKeys'],
                    $options['foreign_keys'],
                    $options['options'],
                    $options['type'],
                    $options['primary'],
                    $options['primary_key'],
                    $options['unique'],
                    $options['index'],
                    $options['nullable']
                );
                $column = $table->addColumn(
                    $columnName,
                    $type
                );
                foreach ($options as $key => $value) {
                    $method = 'set' . ucfirst($key);
                    if (!method_exists($column, $method)) {
                        continue;
                    }
                    $column->$method($value);
                }
                unset($options);
                if (is_array($indexes)) {
                    foreach ($indexes as $indexName => $index) {
                        $columns = $index['columns']??null;
                        if (is_string($columns)) {
                            $columns = [$columns];
                        }

                        if (empty($columns)) {
                            continue;
                        }
                        if (!Filter::shouldStrings(...$columns)) {
                            throw new Exception(
                                'Index columns should be string.'
                            );
                        }
                        $flags = $index['flags'] ?? [];
                        $flags = !is_array($flags) ? [] : $flags;
                        $name = $index['name']??null;
                        $name = !is_string($name) ? $indexName : $name;
                        $name = !is_string($name) ? null : $name;
                        $table->addIndex(
                            $columns,
                            $name,
                            $flags
                        );
                    }
                }
                if (is_array($foreignKeys)) {
                    foreach ($foreignKeys as $foreignName => $foreignKey) {
                        $foreignName = $foreignKey['name']??$foreignName;
                        $foreignName = $foreignName??null;
                        $options = $foreignKey['options']??[];

                        // options, merge from definition and options
                        $options = !is_array($options) ? [] : $options;
                        $options = array_merge($foreignKey, $options);
                        // use prefix
                        $usingPrefix = $options['usePrefix']??null;
                        $usingPrefix = $usingPrefix??($options['use_prefix']??$usePrefix);
                        // foreign table
                        $foreignTable = $foreignKey['foreignTable']??null;
                        $foreignTable = $foreignTable??($foreignKey['foreign_table']??null);
                        $foreignTable = $foreignTable??($foreignKey['table']??null);
                        if (!is_string($foreignTable)) {
                            throw new Exception(
                                'Foreign table should be string.'
                            );
                        }
                        if ($usingPrefix && stripos($foreignTable, $tablePrefix) !== 0) {
                            $foreignTable = $tablePrefix . $foreignTable;
                        }
                        $localColumns = $foreignKey['localColumns']??null;
                        $localColumns = $localColumns??($foreignKey['local_columns']??null);
                        $localColumns = $localColumns??($foreignKey['columns']??null);
                        if (is_string($localColumns)) {
                            $localColumns = [$localColumns];
                        }
                        if (empty($localColumns)) {
                            continue;
                        }
                        if (!Filter::shouldStrings(...$localColumns)) {
                            throw new Exception(
                                'Local columns should be string.'
                            );
                        }
                        $foreignColumns = $foreignKey['foreignColumns']??null;
                        $foreignColumns = $foreignColumns??($foreignKey['foreign_columns']??null);
                        $foreignColumns = $foreignColumns??($foreignKey['references']??null);
                        if (is_string($foreignColumns)) {
                            $foreignColumns = [$foreignColumns];
                        }
                        if (empty($foreignColumns)) {
                            continue;
                        }
                        if (!Filter::shouldStrings(...$foreignColumns)) {
                            throw new Exception(
                                'Foreign columns should be string.'
                            );
                        }
                        unset(
                            $options['use_prefix'],
                            $options['foreignTable'],
                            $options['foreign_table'],
                            $options['table'],
                            $options['localColumns'],
                            $options['local_columns'],
                            $options['columns'],
                            $options['foreignColumns'],
                            $options['foreign_columns'],
                            $options['references'],
                            $options['name']
                        );
                        $table->addForeignKeyConstraint(
                            $foreignTable,
                            $localColumns,
                            $foreignColumns,
                            $options,
                            $foreignName
                        );
                    }
                }
            }
            if (!empty($primaryKeys)) {
                $table->setPrimaryKey($primaryKeys);
            }
            if (!empty($tableUnique)) {
                foreach ($tableUnique as $unique) {
                    $table->addUniqueIndex([$unique]);
                }
            }
            if (!empty($tableIndex)) {
                foreach ($tableIndex as $index) {
                    $table->addIndex([$index]);
                }
            }
            return $table;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Create schema from table definitions.
     *  Table definition array of column definitions also as options.
     *  The key if name or column name if not exists use the key as column name.
     *
     * @param array<string, array{
     *     name: string,
     *     type: string,
     *     options?: array<string, mixed>,
     *     indexes: array<array{
     *           columns: array<string>,
     *           name: string,
     *           flags?: array<string, mixed>
     *      }>
     * }> $tableDefinitions key is table name, value is column definitions.
     * @param bool $usePrefix Use prefix or not. Default is true.
     * @return Schema|SchemaException|Exception Schema instance if success, SchemaException if failed.
     *
     * @uses self::createTableFromDefinition()
     * @uses self::createFromTables()
     */
    public function createSchemaFromDefinitions(
        array $tableDefinitions,
        bool $usePrefix = true
    ): Schema|SchemaException|Exception {
        $tables = [];
        foreach ($tableDefinitions as $tableName => $columnDefinitions) {
            $table = $this->createTableFromDefinition(
                $tableName,
                $columnDefinitions,
                $usePrefix
            );
            if ($table instanceof Exception) {
                return $table;
            }
            $tables[] = $table;
        }
        return $this->createFromTables(...$tables);
    }

    /**
     * Compare schema with table definitions.
     *
     * @param array<string, array{
     *     name: string,
     *     type: string,
     *     options?: array<string, mixed>
     * }> $tableDefinitions
     * @param bool $usePrefix Use prefix or not. Default is true.
     * @return SchemaDiff|SchemaException|Exception SchemaDiff if there is difference, SchemaException if failed.
     *
     * @uses self::createSchemaFromDefinitions()
     * @uses self::getSchemaDiff()
     */
    public function compareSchemaWithDefinitions(
        array $tableDefinitions,
        bool $usePrefix = true
    ): SchemaDiff|SchemaException|Exception {
        $schemaFromDefinitions = $this->createSchemaFromDefinitions(
            $tableDefinitions,
            $usePrefix
        );
        if ($schemaFromDefinitions instanceof Exception) {
            return $schemaFromDefinitions;
        }
        return $this->getSchemaDiff($schemaFromDefinitions);
    }

    /**
     * Get SQL queries from schema diff.
     *
     * @param SchemaDiff $schemaDiff the schema comparison
     * @return string[] the SQL statements needed to update the database schema
     * @throws Exception if there is a problem with the schema
     * @see self::toSafeSchema() for safe mode
     */
    public function toSQL(SchemaDiff $schemaDiff) : array
    {
        return $this
            ->getConnection()
            ->getDatabasePlatform()
            ->getAlterSchemaSQL($schemaDiff);
    }

    /**
     * Create the cloned object from schema diff with removed dropped tables,
     *  dropped columns, and dropped sequences.
     * This method is suitable for save database change for table / column / sequence addition.
     * Generate sql safe mode @uses self::toSQL()
     *
     * @param SchemaDiff $schemaDiff Schema diff.
     * @param bool $removeDropped Remove dropped tables.
     * @param bool $removeDroppedColumns Remove dropped columns.
     * @param bool $removeDroppedSequences Remove dropped sequences.
     * @param bool $removedForeignKeys Remove dropped foreign keys.
     * @param bool $removedIndexes Remove dropped indexes.
     * @return SchemaDiff
     */
    public function toSafeSchema(
        SchemaDiff $schemaDiff,
        bool $removeDropped = true,
        bool $removeDroppedColumns = true,
        bool $removeDroppedSequences = true,
        bool $removedForeignKeys = false,
        bool $removedIndexes = false
    ) : SchemaDiff {
        $schemaDiff  = clone $schemaDiff;
        // remove dropped tables and dropped columns from schema diff
        if ($removeDropped) {
            $schemaDiff->removedTables = [];
        }
        // remove dropped sequences from schema diff
        if ($removeDroppedSequences) {
            $schemaDiff->removedSequences = [];
        }
        // remove dropped columns from schema diff

        foreach ($schemaDiff->changedTables as $tableDiff) {
            if ($removeDroppedColumns) {
                $tableDiff->removedColumns = [];
            }
            if ($removedForeignKeys) {
                $tableDiff->removedForeignKeys = [];
            }
            if ($removedIndexes) {
                $tableDiff->removedIndexes = [];
            }
        }

        return $schemaDiff;
    }
}
