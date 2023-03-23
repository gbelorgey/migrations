<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait DbToolsTrait
{
    /**
     * Alter table
     *
     * @param string table Table's name to alter
     * @param array $rules List of rules to apply in alter request
     * @return self
     */
    public function alterTable($table, array $rules)
    {
        if (empty($table)) {
            $this->abortIf(true, 'Name of table is required');
        }

        if (empty($rules)) {
            $this->abortIf(true, 'Array of rules is required');
        }

        $this->addSql(
            'ALTER TABLE `' . _DB_PREFIX_ . $table . '` ' . implode(', ', $rules)
        );

        return $this;
    }

    /**
     * Drop table
     *
     * @param string table Table's name to drop
     * @return self
     */
    public function dropTable($table)
    {
        if (empty($table)) {
            $this->abortIf(true, 'Name of table is required');
        }

        $this->addSql(
            'DROP TABLE `' . _DB_PREFIX_ . $table . '`;'
        );

        return $this;
    }

    /**
     * Update table
     *
     * @param string table Table's name to alter
     * @param array $rules List of rules to apply in alter request
     * @param array $conditions List of conditions to restrict the request
     * @return self
     */
    public function updateTable($table, array $rules, $conditions = array())
    {
        if (empty($table)) {
            $this->abortIf(true, 'Name of table is required');
        }

        if (empty($rules)) {
            $this->abortIf(true, 'Array of rules is required');
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . $table . '` SET ' . implode(', ', $rules);

        if (!empty($conditions)) {
            $sql .= ' WHERE TRUE AND ' . implode(' AND ', $conditions);
        }

        $this->addSql($sql);

        return $this;
    }
}
