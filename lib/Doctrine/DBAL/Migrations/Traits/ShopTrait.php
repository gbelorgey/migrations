<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait ShopTrait
{
    /**
     * Get all shops
     *
     * @param boolean $active Get shop active (default: true).
     *
     * @return array
     */
    public function getAllShops($active = true)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'shop`
                WHERE TRUE
                AND `active` = ' . boolval($active) . ';';
        
        return $this->connection->fetchAll($sql);
    }

    /**
     * Get shop data for given id shop
     *
     * @param integer $id_shop ID shop.
     *
     * @return array
     */
    public function getShopById($id_shop)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'shop`
                WHERE TRUE
                AND `id_shop` = ' . intval($id_shop) . ';';

        return $this->connection->fetchAll($sql);
    }
}
