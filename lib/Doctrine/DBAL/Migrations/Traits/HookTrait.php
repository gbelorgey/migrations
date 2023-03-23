<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait HookTrait
{
    /**
     * Add a hook
     *
     * @param string $name Hook's name
     * @param string $title Hook's title
     * @param string $description Hook's description
     * @return self
     */
    protected function addHook($name, $title = null, $description = null)
    {
        $this->abortIf(empty($name), 'Name of hook is required');

        $this->connection->executeQuery(
            'INSERT INTO `' . _DB_PREFIX_ . 'hook`
                (
                    `name`
                    , `title`
                    , `description`
                    , `position`
                )
            VALUES
                (
                    ' . $this->connection->quote($name) . '
                    , ' . $this->connection->quote($title) . '
                    , ' . $this->connection->quote($description) . '
                    , 1
                )
            ;'
        );

        return $this;
    }

    /**
     * Delete hook
     *
     * @param string $name Hook's name
     * @return self
     */
    protected function deleteHook($name)
    {
        $this->abortIf(empty($name), 'Name hook is required');

        $id = $this->getHookId($name);

        $this->addSql(
            'DELETE FROM `' . _DB_PREFIX_ . 'hook`
            WHERE `id_hook` = ' . (int)$id . ';'
        );

        return $this;
    }

    /**
     * Get hook ID by name
     *
     * @param string $name Hook's name
     * @return integer
     */
    protected function getHookId($name)
    {
        $this->abortIf(empty($name), 'Name hook is required');

        $sql = 'SELECT `id_hook`
            FROM ' . _DB_PREFIX_ . 'hook
            WHERE `name` = ' . $this->connection->quote($name) . ';';

        return (int)$this->connection->fetchColumn($sql);
    }

    protected function getHookMaxPosition($id_hook)
    {
        $this->abortIf(empty($id_hook), 'ID hook is required');

        return (int) $this->connection->fetchColumn(
            'SELECT MAX(`position`) '
            . 'FROM `'._DB_PREFIX_.'hook_module` '
            . 'WHERE `id_hook` =' . (int)$id_hook
        );
    }
}
