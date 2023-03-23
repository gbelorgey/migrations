<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait ModuleTrait
{
    /**
     * Active a module
     *
     * @param string $name Module's name
     * @return self
     */
    public function activateModule($name)
    {
        $id = $this->getModuleId($name);

        if (!$id) {
            return $this;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'module`
                SET `active` = 1
                WHERE 1
                AND `id_module` = :id;';

        $this->addSql($sql, ['id' => $id]);

        return $this;
    }

    /**
     * Add a module
     *
     * @param string $name Module's name
     * @param string $version Module's version
     * @param bool $active Is the module active (default `true`)
     * @param bool $force_default_config
     * @return self
     */
    public function addModule($name, $version, $active = true, $force_default_config = true)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'module`
                  (
                      `name`
                    , `active`
                    , `version`
                  )
                VALUES
                  (
                      ' . $this->connection->quote($name) . '
                    , ' . ($active ? 1 : 0) . '
                    , ' . $this->connection->quote($version) . '
                  );';

        $this->connection->executeQuery($sql);
        $id = (int) $this->connection->lastInsertId();

        if ($active) {
            $this->activateModule($name);
        }

        if ($force_default_config) {
            $this->addModuleAccess($name);
            $this->addModuleCountries($id);
            $this->addModuleGroups($id);
            $this->addModuleShops($id);
        }

        return $this;
    }

    /**
     * Add access for a module
     *
     * @param integer $id_module ID of the module
     * @param array $access List of access of module
     * @return self
     */
    public function addModuleAccess($name, $access = array())
    {
        $this->abortIf(empty($name), 'Module name is required to add access');

        // If empty access, add default access
        if (empty($access)) {
            $profiles = $this->connection->fetchAll(
                'SELECT `id_profile` '
                . 'FROM `'._DB_PREFIX_.'profile_lang` '
                . 'WHERE `name` != "SuperAdmin" '
                . 'GROUP BY `id_profile`;'
            );

            foreach ($profiles as $profile) {
                $access[$profile['id_profile']] = [];
            }
        }

        $slug_prefix = 'ROLE_MOD_MODULE_' . $name . '_';
        foreach ($access as $id_profile => $permissions) {
            $rights = [];
            if ($id_profile == 1) {
                $rights[] = 'CREATE';
                $rights[] = 'READ';
                $rights[] = 'UPDATE';
                $rights[] = 'DELETE';
            } else {
                $rights = array_map('strtoupper', $permissions);
            }

            foreach ($rights as $right) {
                $slug = $slug_prefix . $right;

                $this->connection->executeQuery(
                    'INSERT INTO `' . _DB_PREFIX_ . 'authorization_role`
                    (
                        `slug`
                    )
                    VALUES (
                        "' . $slug . '"
                    );'
                );

                $id_authorization_role = intval($this->connection->lastInsertId());

                $this->addSql(
                    'INSERT INTO `' . _DB_PREFIX_ . 'module_access`
                    (
                        `id_profile`,
                        `id_authorization_role`
                    )
                    VALUES (
                        ' . $id_profile . ',
                        ' . $id_authorization_role . '
                    );'
                );
            }
        }

        return $this;
    }

    /**
     * Add countries for a module
     *
     * @param integer $id_module ID of the module
     * @param array $countries List of ID country for module
     * @return self
     */
    public function addModuleCountries($id_module, $countries = array())
    {
        $this->abortIf(empty($id_module), 'ID module is required to add country');

        // If empty country, add all countries
        if (empty($countries)) {
            $countries = $this->connection->fetchAll(
                'SELECT `id_country`, `id_shop` '
                . 'FROM `'._DB_PREFIX_.'country_shop` '
            );
        }

        foreach ($countries as $country) {
            $this->addSql(
                'INSERT INTO `' . _DB_PREFIX_ . 'module_country` '
                . '(`id_module`, `id_shop`, `id_country`) '
                . 'VALUES (
                    ' . (int)$id_module . '
                    , ' . (int)$country['id_shop'] . '
                    , ' . (int)$country['id_country'] . '
                )'
            );
        }

        return $this;
    }

    /**
     * Add groups for a module
     *
     * @param integer $id_module ID of the module
     * @param array $groups List of ID group for module
     * @return self
     */
    public function addModuleGroups($id_module, $groups = array())
    {
        $this->abortIf(empty($id_module), 'ID module is required to add group');

        // If empty group, add all group
        if (empty($groups)) {
            $groups = $this->connection->fetchAll(
                'SELECT `id_group`, `id_shop` '
                . 'FROM `'._DB_PREFIX_.'group_shop` '
            );
        }

        foreach ($groups as $group) {
            $this->addSql(
                'INSERT INTO `' . _DB_PREFIX_ . 'module_group` '
                . '(`id_module`, `id_shop`, `id_group`) '
                . 'VALUES (
                    ' . (int)$id_module . '
                    , ' . (int)$group['id_shop'] . '
                    , ' . (int)$group['id_group'] . '
                )'
            );
        }

        return $this;
    }

    /**
     * Add module hooks
     *
     * @param integer $id_module ID of the module
     * @param array $hooks List of name's hook to link to module
     * @return self
     */
    public function addModuleHooks($id_module, $hooks = array())
    {
        $this->abortIf(empty($id_module), 'ID hook is required');
        $this->abortIf(empty($hooks), 'List of hooks is required');

        foreach ($hooks as $hook_name) {
            $id_hook = $this->getHookId($hook_name);

            if (empty($id_hook)) {
                continue;
            }

            $position = $this->getHookMaxPosition($id_hook) + 1;
            $this->addSql(
                'INSERT INTO `' . _DB_PREFIX_ . 'hook_module` '
                . '(`id_module`, `id_hook`, `position`) '
                . 'VALUES (
                    ' . (int)$id_module . '
                    , ' . (int)$id_hook . '
                    , ' . (int)$position . '
                )'
            );
        }

        return $this;
    }

    /**
     * Add shops for a module
     *
     * @param integer $id_module ID of the module
     * @param array $shops List of ID shop for module
     * @return self
     */
    public function addModuleShops($id_module, $shops = array())
    {
        $this->abortIf(empty($id_module), 'ID module is required to add shop');

        // If empty group, add all shops
        if (empty($shops)) {
            $shops = $this->connection->fetchAll(
                'SELECT `id_shop` '
                . 'FROM `'._DB_PREFIX_.'shop` '
            );
        }

        foreach ($shops as $shop) {
            if (empty($shop['enable_device'])) {
                $shop['enable_device'] = 7;
            }

            $this->addSql(
                'INSERT INTO `' . _DB_PREFIX_ . 'module_shop` '
                . '(`id_module`, `id_shop`, `enable_device`) '
                . 'VALUES (
                    ' . (int)$id_module . '
                    , ' . (int)$shop['id_shop'] . '
                    , ' . (int)$shop['enable_device'] . '
                )'
            );
        }

        return $this;
    }

    /**
     * Unactive a module
     *
     * @param string $name Module's name
     * @return self
     */
    public function deactivateModule($name)
    {
        $id = $this->getModuleId($name);

        if (!$id) {
            return $this;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'module`
                SET `active` = 0
                WHERE 1
                AND `id_module` = :id;';

        $this->addSql($sql, ['id' => $id]);

        return $this;
    }

    /**
     * Remove a module
     *
     * @param string $name Module's name
     * @return self
     */
    public function deleteModule($name)
    {
        $id = $this->getModuleId($name);

        $tables = [
            'hook_module_exceptions',
            'module',
        ];

        foreach ($tables as $table) {
            $sql = 'DELETE FROM `' . _DB_PREFIX_ . $table . '` '
                . 'WHERE TRUE '
                . 'AND `id_module` = :id;'
            ;

            $this->addSql($sql, ['id' => $id]);
        }

        $this->deleteModuleAccess($name);
        $this->deleteModuleCountries($id);
        $this->deleteModuleGroups($id);
        $this->deleteModuleHooks($id);
        $this->deleteModuleShops($id);

        return $this;
    }

    /**
     * Remove all access for a module
     *
     * @param integer $id_module ID of the module
     * @return self
     */
    public function deleteModuleAccess($name)
    {
        $this->abortIf(empty($name), 'Module name is required');

        $rights = [];

        $rights[] = 'CREATE';
        $rights[] = 'READ';
        $rights[] = 'UPDATE';
        $rights[] = 'DELETE';

        $slug_prefix = 'ROLE_MOD_MODULE_' . $name . '_';

        foreach ($rights as $right) {
            $slug = $slug_prefix . $right;

            $id_autorization_role = intval($this->connection->fetchColumn(
                'SELECT `id_authorization_role`'
                . ' FROM `' . _DB_PREFIX_ . 'authorization_role`'
                . ' WHERE `slug` = "' . $slug . '"'
            ));

            $this->addSql(
                'DELETE FROM `' . _DB_PREFIX_ . 'module_access`'
                . ' WHERE `id_authorization_role` = ' . $id_autorization_role . ';'
            );

            $this->addSql(
                'DELETE FROM `' . _DB_PREFIX_ . 'authorization_role`'
                . ' WHERE `id_authorization_role` = ' . $id_autorization_role . ';'
            );
        }

        return $this;
    }

    /**
     * Remove all country for a module
     *
     * @param integer $id_module ID of the module
     * @return self
     */
    public function deleteModuleCountries($id_module)
    {
        $this->abortIf(empty($id_module), 'ID module is required');

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'module_country` '
            . 'WHERE `id_module` = '.(int)$id_module.';'
        );

        return $this;
    }

    /**
     * Remove all groups for a module
     *
     * @param integer $id_module ID of the module
     * @return self
     */
    public function deleteModuleGroups($id_module)
    {
        $this->abortIf(empty($id_module), 'ID module is required');

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'module_group` '
            . 'WHERE `id_module` = '.(int)$id_module.';'
        );

        return $this;
    }

    /**
     * Remove all hooks for a module
     *
     * @param string $id_module ID of the module
     * @return self
     */
    public function deleteModuleHooks($id_module)
    {
        $this->abortIf(empty($id_module), 'ID module is required');

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'hook_module` '
            . 'WHERE `id_module` = '.(int)$id_module.';'
        );

        return $this;
    }

    /**
     * Remove all shop for a module
     *
     * @param integer $id_module ID of the module
     * @return self
     */
    public function deleteModuleShops($id_module)
    {
        $this->abortIf(empty($id_module), 'ID module is required');

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'module_shop` '
            . 'WHERE `id_module` = '.(int)$id_module.';'
        );

        return $this;
    }

    /**
     * Get a module ID
     *
     * @param string $name Module's name
     * @return integer
     */
    public function getModuleId($name)
    {
        $this->abortIf(empty($name), 'Name module is required');

        $sql = 'SELECT `id_module`
                FROM `' . _DB_PREFIX_ . 'module`
                WHERE 1
                AND `name` = ' . $this->connection->quote($name) . ';';

        return (int) $this->connection->fetchColumn($sql);
    }
}
