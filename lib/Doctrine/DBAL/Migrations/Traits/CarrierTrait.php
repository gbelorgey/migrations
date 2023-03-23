<?php

namespace Doctrine\DBAL\Migrations\Traits;

/* @todo Repplace by the future LanguageTrait */
use Language;

trait CarrierTrait
{
    /**
     * Add carrier
     *
     * @param string $name Name of the carrier
     * @param array $langs List of langs with value like delay of the carrier
     * @param array $params List of parameters of the carrier
     * @return self
     */
    public function addCarrier($name, $params = array(), $force_default_config = true)
    {
        $id_carrier_last = $this->connection->fetchColumn(
            'SELECT `id_carrier` '
            . 'FROM `'._DB_PREFIX_.'carrier` '
            . 'ORDER BY `id_carrier` DESC;'
        );

        $check_params = $this->getCarrierParams();
        $this->validateExpectations(
            $check_params['expectations'],
            $check_params['optional'],
            $params
        );

        $id_carrier = $id_carrier_last + 1;

        $id_tax_rules_group = 0;
        $url = 'NULL';
        $active = 0;
        $deleted = 0;
        $shipping_handling = 1;
        $range_behavior = 0;
        $is_module = 0;
        $is_free = 0;
        $shipping_external = 0;
        $need_range = 0;
        $external_module_name = 'NULL';
        $shipping_method = 0;
        $position = 0;
        $max_width = 0;
        $max_height = 0;
        $max_depth = 0;
        $max_weight = 0.000000;
        $grade = 0;

        foreach ($params as $key => $param_optional) {
            if (in_array($key, $check_params['optional'])) {
                $value = $params[$key];

                if (is_numeric($value)) {
                    $value = intval($value);
                } else {
                    $value = $this->connection->quote($value);
                }

                $$key =  $value;
            }
        }

        $this->connection->executeQuery(
            'INSERT INTO `'._DB_PREFIX_.'carrier`
                (
                    `id_carrier`,
                    `id_reference`,
                    `id_tax_rules_group`,
                    `name`,
                    `url`,
                    `active`,
                    `deleted`,
                    `shipping_handling`,
                    `range_behavior`,
                    `is_module`,
                    `is_free`,
                    `shipping_external`,
                    `need_range`,
                    `external_module_name`,
                    `shipping_method`,
                    `position`,
                    `max_width`,
                    `max_height`,
                    `max_depth`,
                    `max_weight`,
                    `grade`
                )
             VALUES
                (
                    ' . (int)$id_carrier . '
                    , ' . (int)$id_carrier . '
                    , ' . $id_tax_rules_group . '
                    , ' . $this->connection->quote($name) . '
                    , ' . $url . '
                    , ' . $active . '
                    , ' . $deleted . '
                    , ' . $shipping_handling . '
                    , ' . $range_behavior . '
                    , ' . $is_module . '
                    , ' . $is_free . '
                    , ' . $shipping_external . '
                    , ' . $need_range . '
                    , ' . $external_module_name . '
                    , ' . $shipping_method . '
                    , ' . $position . '
                    , ' . $max_width . '
                    , ' . $max_height . '
                    , ' . $max_depth . '
                    , ' . $max_weight . '
                    , ' . $grade . '
                )
            '
        );

        if ($force_default_config) {
            $this->addCarrierGroups($id_carrier);
            $this->addCarrierZones($id_carrier);
            $this->addCarrierLangsAndShops($id_carrier);
        }

        return $id_carrier;
    }

    /**
     * Add delivery for a carrier
     *
     * @param integer $id_carrier ID of the carrier
     * @param array $range_prices List of range prices
     * @return self
     */
    public function addCarrierDeliveries($id_carrier, $deliveries)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for addCarrierDeliveries');
        $this->abortIf(empty($deliveries), 'List of deliveries is required');

        foreach ($deliveries as $delivery) {
            $id_shop = 'NULL';
            $id_shop_group = 'NULL';
            $id_range_price = 'NULL';
            $id_range_weight = 'NULL';
            $id_zone = 0;
            $price = (float)$delivery['price'];

            if (!empty($delivery['id_shop'])) {
                $id_shop = (int)$delivery['id_shop'];
            }

            if (!empty($delivery['id_shop_group'])) {
                $id_shop_group = (int)$delivery['id_shop_group'];
            }

            if (!empty($delivery['id_range_price'])) {
                $id_range_price = (int)$delivery['id_range_price'];
            }

            if (!empty($delivery['id_range_weight'])) {
                $id_range_weight = (int)$delivery['id_range_weight'];
            }

            if (!empty($delivery['id_zone'])) {
                $id_zone = (int)$delivery['id_zone'];
            }

            $this->addSql(
                'INSERT INTO `'._DB_PREFIX_.'delivery`
                    (
                        `id_carrier`
                        , `id_shop`
                        , `id_shop_group`
                        , `id_range_price`
                        , `id_range_weight`
                        , `id_zone`
                        , `price`
                    )
                  VALUES
                   (
                        ' . (int)$id_carrier . '
                        , ' . $id_shop . '
                        , ' . $id_shop_group . '
                        , ' . $id_range_price . '
                        , ' . $id_range_weight . '
                        , ' . $id_zone . '
                        , ' . $price . '
                    );
                '
            );
        }

        return $this;
    }

    /**
     * Add groups for a carrier
     *
     * @param integer $id_carrier ID of the carrier
     * @param array $groups List of groups
     * @return self
     */
    public function addCarrierGroups($id_carrier, $groups = array())
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required');

        if (empty($groups)) {
            $groups = $this->connection->fetchAll('SELECT `id_group` FROM `' . _DB_PREFIX_ . 'group`');
        }

        foreach ($groups as $group) {
            $this->addSql(
                'INSERT INTO `'._DB_PREFIX_.'carrier_group`
                    (
                        `id_carrier`,
                        `id_group`
                    )
                 VALUES
                    (
                        ' . (int)$id_carrier . ',
                        ' . (int)$group['id_group'] . '
                    );
                '
            );
        }

        return $this;
    }


    /**
     * Add shops and languages for a carrier
     *
     * @param integer $id_carrier ID of the carrier
     * @param array $shops List of shops
     * @param array $langs List of languages
     * @return self
     */
    public function addCarrierLangsAndShops($id_carrier, $langs = array(), $shops = array())
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required');

        if (empty($langs)) {
            $data_langs = $this->connection->fetchAll('SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'lang` WHERE `active` = 1');
            foreach ($data_langs as $data_lang) {
                $langs[$data_lang['iso_code']] = ['delay' => ''];
            }
        }

        if (empty($shops)) {
            $shops = $this->connection->fetchAll('SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'shop` WHERE `active` = 1');
        }

        foreach ($shops as $shop) {
            $this->addSql(
                'INSERT INTO `'._DB_PREFIX_.'carrier_shop`
                    (
                        `id_carrier`,
                        `id_shop`
                    )
                  VALUES
                   (
                        ' . (int)$id_carrier . ',
                        ' . (int)$shop['id_shop'] . '
                    );
                '
            );

            foreach ($langs as $iso_code => $value) {
                if (empty($value['delay'])) {
                    $value['delay'] = '';
                }

                $id_lang = Language::getIdByIso($iso_code);
                $this->addSql(
                    'INSERT INTO `'._DB_PREFIX_.'carrier_lang`
                        (
                            `id_carrier`,
                            `id_shop`,
                            `id_lang`,
                            `delay`
                        )
                     VALUES
                        (
                            ' . (int)$id_carrier . ',
                            ' . (int)$shop['id_shop'] . ',
                            ' . (int)$id_lang . ',
                            ' . $this->connection->quote($value['delay']) . '
                        )
                    '
                );
            }
        }

        return $this;
    }

    /**
     * Add range price for a carrier
     *
     * @param integer $id_carrier ID of the carrier
     * @param float $delimiter1
     * @param float $delimiter2
     * @return integer
     */
    public function addCarrierRangePrice($id_carrier, $delimiter1, $delimiter2)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required');
        $this->abortIf(empty($delimiter1), 'Delimiter 1 is required');
        $this->abortIf(empty($delimiter2), 'Delimiter 2 is required');

        $this->connection->executeQuery(
            'INSERT INTO `'._DB_PREFIX_.'range_price`
                (
                    `id_carrier`
                    , `delimiter1`
                    , `delimiter2`
                )
              VALUES
               (
                    ' . (int)$id_carrier . '
                    , ' . (int)$delimiter1 . '
                    , ' . (int)$delimiter2 . '
                );
            '
        );

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Add zones for a carrier
     *
     * @param integer $id_carrier ID of the carrier
     * @param array $zones List of zones
     * @return self
     */
    public function addCarrierZones($id_carrier, $zones = array())
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required');

        if (empty($zones)) {
            $zones = $this->connection->fetchAll('SELECT `id_zone` FROM `' . _DB_PREFIX_ . 'zone` WHERE `active` = 1');
        }

        foreach ($zones as $zone) {
            $this->addSql(
                'INSERT INTO `'._DB_PREFIX_.'carrier_zone`
                    (
                        `id_carrier`,
                        `id_zone`
                    )
                  VALUES
                   (
                        ' . (int)$id_carrier . ',
                        ' . (int)$zone['id_zone'] . '
                    );
                '
            );
        }

        return $this;
    }

    /**
     * Remove carrier
     *
     * @param string $name Name of the carrier
     * @return self
     */
    public function deleteCarrier($name)
    {
        $id_carrier = $this->getCarrierId($name);

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'carrier` '
            . 'WHERE `id_carrier`= ' . (int)$id_carrier . ';'
        );

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'carrier_lang` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        $this->deleteCarrierDeliveries($id_carrier);
        $this->deleteCarrierGroups($id_carrier);
        $this->deleteCarrierRangePrice($id_carrier);
        $this->deleteCarrierShops($id_carrier);
        $this->deleteCarrierZones($id_carrier);

        return $this;
    }

    /**
     * Remove carrier deliveries
     *
     * @param integer $id_carrier ID of the carrier
     * @return self
     */
    public function deleteCarrierDeliveries($id_carrier)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for deleteCarrierDeliveries');

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'delivery` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        return $this;
    }

    /**
     * Remove carrier groups
     *
     * @param integer $id_carrier ID of the carrier
     * @return self
     */
    public function deleteCarrierGroups($id_carrier)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for deleteCarrierGroups');

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'carrier_group` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        return $this;
    }

    /**
     * Remove carrier shops
     *
     * @param integer $id_carrier ID of the carrier
     * @return self
     */
    public function deleteCarrierShops($id_carrier)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for deleteCarrierShops');

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'carrier_shop` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        return $this;
    }

    /**
     * Remove carrier range prices
     *
     * @param integer $id_carrier ID of the carrier
     * @return self
     */
    public function deleteCarrierRangePrice($id_carrier)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for deleteCarrierRangePrice');

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'range_price` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        return $this;
    }

    /**
     * Remove carrier zones
     *
     * @param integer $id_carrier ID of the carrier
     * @return self
     */
    public function deleteCarrierZones($id_carrier)
    {
        $this->abortIf(empty($id_carrier), 'ID of carrier is required for deleteCarrierZones');

        $this->addSql('
            DELETE FROM `'._DB_PREFIX_.'carrier_zone` '
            . 'WHERE `id_carrier` = ' . (int)$id_carrier
        );

        return $this;
    }

    /**
     * Get carrier ID by name
     *
     * @param string $name Name of the carrier
     * @return integer
     */
    public function getCarrierId($name)
    {
        $this->abortIf(empty($name), 'Name of carrier is required for getCarrierId');

        return (int)$this->connection->fetchColumn(
            'SELECT `id_carrier` '
            . 'FROM ' . _DB_PREFIX_ . 'carrier '
            . 'WHERE `name` = ' . $this->connection->quote($name)
        );
    }

    /**
     * Get carrier max postion
     *
     * @return integer
     */
    public function getCarrierMaxPosition()
    {
        return (int) $this->connection->fetchColumn(
            'SELECT MAX(`position`) '
            . 'FROM `'._DB_PREFIX_.'carrier`'
        );
    }

    public function getCarrierParams()
    {
        return [
            'expectations' => [],
            'optional' => [
                'id_tax_rules_group',
                'active',
                'url',
                'deleted',
                'shipping_handling',
                'range_behavior',
                'is_module',
                'is_free',
                'shipping_external',
                'need_range',
                'external_module_name',
                'shipping_method',
                'position',
                'max_width',
                'max_height',
                'max_depth',
                'max_weight',
                'grade',
            ]
        ];
    }
}
