<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait TabTrait
{
    use AccessTrait;

    /**
     * Add an admin tab
     *
     * @param integer $id_tab_parent ID used to categorise the new tab
     * @param string $controller Controller's class name use by the new created tab
     * @param string $name Name shown in admin menu (for every admin's language)
     * @return self
     */
    public function addTab($id_tab_parent, $controller, $name, $module = null, $icon = null, $enabled = true)
    {
        $position = $this->connection->fetchColumn('
            SELECT `position` + 1
            FROM `'._DB_PREFIX_.'tab`
            WHERE `id_parent` =' . (int)$id_tab_parent . '
            ORDER BY `position` DESC;
        ');

        if (empty($module)) {
            $module = 'NULL';
        } else {
            $module = $this->connection->quote($module);
        }

        if (empty($icon)) {
            $icon = 'NULL';
        } else {
            $icon = $this->connection->quote($icon);
        }

        $this->connection->executeQuery(
            'INSERT INTO `'._DB_PREFIX_.'tab`
                (
                    `id_parent`
                    , `class_name`
                    , `position`
                    , `module`
                    , `icon`
                    , `enabled`
                )
             VALUES
                (
                    ' . (int)$id_tab_parent . '
                    , ' . $this->connection->quote($controller) . '
                    , ' . (int)$position . '
                    , ' . $module . '
                    , ' . $icon . '
                    , ' . ($enabled ? 1 : 0) . '
                )
            ;'
        );

        $id_tab = (int) $this->connection->lastInsertId();

        $this->addSql(
            'INSERT INTO `'._DB_PREFIX_.'tab_lang`
                (
                    `id_tab`,
                    `id_lang`,
                    `name`
                )
            SELECT
                '.$id_tab.',
                `id_lang`,
                ' . $this->connection->quote($name) .'
            FROM `' . _DB_PREFIX_ . 'lang`
            ;
            '
        );

        // Add permissions for BO
        $this->addAccess($id_tab);

        return $this;
    }

    /**
     * Change an admin tab parent
     *
     * @param string $controller Controller to change
     * @param integer $id_tab_parent ID of the new parent
     * @return self
     */
    public function changeParent($controller, $parent_id)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `id_parent` = ' . $this->connection->quote($parent_id) . '
                WHERE 1
                AND `class_name` = ' . $this->connection->quote($controller) . '
            ;'
        ;

        $this->addSql($sql);

        return $this;
    }

    /**
     * Remove an admin tab
     *
     * @param string $controller Controller to remove
     * @return self
     */
    public function deleteTab($controller)
    {
        $id_tab = $this->getTabId($controller);
        $parent_id = $this->getParentTabId($controller);

        $this->addSql('DELETE FROM `'._DB_PREFIX_.'tab_lang` WHERE `id_tab`='.(int)$id_tab);

        // Delete permissions for BO
        $this->deleteAccess($id_tab);

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'tab`
            WHERE `class_name` = ' . $this->connection->quote($controller)
        );

        $this->reorderTabs($parent_id);

        return $this;
    }

    /**
     * Get controller name for an admin tab
     *
     * @param int $id_tab ID of the tab
     * @return string
     */
    public function getTabControllerName($id_tab)
    {
        $sql = 'SELECT `class_name`
                FROM `' . _DB_PREFIX_ . 'tab`
                WHERE 1
                AND `id_tab` = ' . intval($id_tab) . ';';

        return $this->connection->fetchColumn($sql);
    }

    /**
     * Get an admin tab ID
     *
     * @param string $controller Controller's class name you looking for
     * @return integer
     */
    public function getTabId($controller)
    {
        $sql = 'SELECT `id_tab`
                FROM `' . _DB_PREFIX_ . 'tab`
                WHERE 1
                AND `class_name` = ' . $this->connection->quote($controller) . ';';

        return (int) $this->connection->fetchColumn($sql);
    }

    /**
     * Get an admin tab parent's ID
     *
     * @param string $controller Controller's class name you looking for
     * @return integer
     */
    public function getParentTabId($controller)
    {
        $sql = 'SELECT `id_parent`
                FROM `' . _DB_PREFIX_ . 'tab`
                WHERE 1
                AND `class_name` = ' . $this->connection->quote($controller) . ';';

        return (int) $this->connection->fetchColumn($sql);
    }

    /**
     * Move a tab after another one
     *
     * @param string $controller Controller to move
     * @param string $target Reference controller
     * @return self
     */
    public function setTabAfter($controller, $target)
    {
        return $this->setTabPosition($controller, $target, true);
    }

    /**
     * Move a tab before another one
     *
     * @param string $controller Controller to move
     * @param string $target Reference controller
     * @return self
     */
    public function setTabBefore($controller, $target)
    {
        return $this->setTabPosition($controller, $target, false);
    }

    /**
     * Set icon name from https://material.io/tools/icons/?style=baseline to tab
     *
     * @param string $controller Controller to apply an icon
     * @param string $icon Icon name from https://material.io/tools/icons/?style=baseline
     * @return self
     */
    public function setTabIcon($controller, $icon)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `icon` = ?
                WHERE 1
                AND `class_name` = ?
                ;';

        return $this->connection->executeQuery($sql, [$icon, $controller]);
    }

    /**
     * Move a tab after or before another one
     *
     * @param string $controller Controller to move
     * @param string $target Reference controller
     * @param bool $after Position after the target (set to `false` to position before)
     * @return self
     */
    public function setTabPosition($controller, $target, $after = true)
    {
        $parent_id = $this->getParentTabId($controller);

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `position` = `position` * 10
                WHERE 1
                AND `id_parent` = ' . $this->connection->quote($parent_id) . '
                ;';

        $this->connection->executeQuery($sql);

        $sql = 'SELECT `position`
                FROM `' . _DB_PREFIX_ . 'tab`
                WHERE 1
                AND `class_name` = ' . $this->connection->quote($target) . '
                ;';

        $position = (int) $this->connection->fetchColumn($sql);

        if ($after) {
            $position += 5;
        } else {
            $position -= 5;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `position` = ?
                WHERE 1
                AND `class_name` = ?
                ;';

        $this->connection->executeQuery($sql, [$position, $controller]);

        $this->reorderTabs($parent_id);

        return $this;
    }

    /**
     * Reorder every tabs in a group
     *
     * @param integer $parent_id ID of the "group" to reorder
     * @return self
     */
    public function reorderTabs($parent_id)
    {
        $position = 1;

        $sql = 'SELECT `id_tab`
                FROM `' . _DB_PREFIX_ . 'tab`
                WHERE 1
                AND `id_parent` = ' . $this->connection->quote($parent_id) . '
                ORDER BY `position` ASC;';

        $tabs = $this->connection->fetchAll($sql);

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `position` = ?
                WHERE 1
                AND `id_tab` = ?;';

        foreach ($tabs as $tab) {
            $this->connection->executeQuery($sql, [$position, $tab['id_tab']]);
            $position++;
        }

        return $this;
    }
}
