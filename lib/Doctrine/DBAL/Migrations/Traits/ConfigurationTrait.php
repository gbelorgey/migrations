<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait ConfigurationTrait
{
    /**
     * Add a configuration
     *
     * @param string $name Configuration's name you looking for
     * @param string $value Configuration's value |
     * array(['iso'=>'value']) $value Give an array with iso code as key and associated value to create configuration_lang
     * @param string $iso Iso code of the configuration's id_lang
     * @param int Configuration's shop_group id
     * @param int Configuration's shop id
     * @return void
     */
    protected function addConfig($name, $value = null, $id_shop_group = null, $id_shop = null)
    {
        $id_shop_group = (int)$id_shop_group;
        $id_shop = (int)$id_shop;

        if (empty($id_shop_group)) {
            $id_shop_group = 'NULL';
        }

        if (empty($id_shop)) {
            $id_shop = 'NULL';
        }

        $tmp = 'NULL';
        if (!is_array($value)) {
            $tmp = $this->connection->quote($value);
        }

        $this->connection->executeQuery(
            'INSERT INTO `' . _DB_PREFIX_ . 'configuration`
                (
                    `id_shop_group`,
                    `id_shop`,
                    `name`,
                    `value`,
                    `date_add`,
                    `date_upd`
                )
            VALUES
                (
                    ' . $id_shop_group . ',
                    ' . $id_shop . ',
                    ' . $this->connection->quote($name) . ',
                    ' . $tmp . ',
                    NOW(),
                    NOW()
                )
            ;'
        );

        if ($tmp === 'NULL') {
            $id_conf = (int)$this->connection->lastInsertId();
            foreach ($value as $lang => $val) {
                $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'configuration_lang`
                            (
                                `id_configuration`,
                                `id_lang`,
                                `value`,
                                `date_upd`
                            )
                        SELECT
                            ' . (int)$id_conf . ',
                            `id_lang`,
                            ' . $this->connection->quote($val) . ',
                            NOW()
                        FROM `' . _DB_PREFIX_ . 'lang`
                        WHERE `iso_code` = "' . $lang . '"
                ;';

                $this->addSql($sql);
            }
        }
    }

    /**
     * Delete configuration by name
     *
     * @param string $name Configuration's name you looking for
     * @return integer
     */
    protected function deleteConfig($name)
    {
        $id_config = $this->getConfigId($name);
        $this->addSql(
            'DELETE FROM `' . _DB_PREFIX_ . 'configuration_lang`
            WHERE `id_configuration` = ' . $id_config . ';'
        );
        $this->addSql(
            'DELETE FROM `' . _DB_PREFIX_ . 'configuration`
            WHERE `name` = ' . $this->connection->quote($name) . ';'
        );
    }

    /**
     * Get configuration ID by name
     *
     * @param string $name Configuration's name you looking for.
     * @return integer
     */
    protected function getConfigId($name)
    {
        $sql = 'SELECT `id_configuration`
            FROM ' . _DB_PREFIX_ . 'configuration
            WHERE `name` = ' . $this->connection->quote($name) . ';';

        return (int)$this->connection->fetchColumn($sql);
    }

    protected function setConfig($name, $value, $id_shop_group = null, $id_shop = null)
    {
        $check = (int)\Db::getInstance()->getValue(
            'SELECT `id_configuration`
            FROM `' . _DB_PREFIX_ . 'configuration`
            WHERE name = ' . $this->connection->quote($name)
        );

        if ($check > 0) {
            $this->updateConfig($name, $value, $id_shop_group, $id_shop);
        } else {
            $this->addConfig($name, $value, $id_shop_group, $id_shop);
        }
    }

    protected function updateConfig($name, $value, $id_shop_group = null, $id_shop = null)
    {
        $query = 'UPDATE `' . _DB_PREFIX_ . 'configuration`
            SET `value` = ' . $this->connection->quote($value) . '
            WHERE `name` = ' . $this->connection->quote($name);

        if (!empty($id_shop_group)) {
            $query .= ' AND `id_shop_group` = ' . (int)$id_shop_group;
        }

        if (!empty($id_shop)) {
            $query .= ' AND `id_shop` = ' . (int)$id_shop;
        }

        $this->addSql($query);
    }

    /**
     * Update a configuration_lang
     *
     * @param string $name Configuration's name you looking for
     * @param string $value Configuration's value
     * @param string $iso Iso code of the configuration's id_lang
     * @param int Configuration's shop_group id
     * @param int Configuration's shop id
     * @return void
     */
    protected function updateConfigLang($name, $value, $iso, $id_shop_group = null, $id_shop = null)
    {

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration_lang` AS `conf_lang`
                RIGHT JOIN `' . _DB_PREFIX_ . 'configuration` AS `conf`
                    ON `conf`.`id_configuration` = `conf_lang`.`id_configuration`
                RIGHT JOIN `' . _DB_PREFIX_ . 'lang` AS `lang`
                    ON `conf_lang`.`id_lang` = `lang`.`id_lang`
                SET `conf_lang`.`value` = ' . $this->connection->quote($value) . '
                WHERE 1
                    AND `conf`.`name` = ' . $this->connection->quote($name) . '
                    AND `lang`.`iso_code` = "' . $iso . '"';

        if (!empty($id_shop_group)) {
            $sql .= ' AND `conf`.`id_shop_group` = ' . (int)$id_shop_group;
        }

        if (!empty($id_shop)) {
            $sql .= ' AND `conf`.`id_shop` = ' . (int)$id_shop;
        }

        $this->addSql($sql);
    }
}
