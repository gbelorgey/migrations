<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait AccessTrait
{
    /**
     * Add access for an admin tab
     *
     * @param integer $id_tab ID of the tab
     * @return self
     */
    public function addAccess($id_tab)
    {
        $profiles = $this->connection->fetchAll(
            'SELECT `id_profile`
            FROM `' . _DB_PREFIX_ . 'profile`;'
        );

        $controller_name = $this->getTabControllerName($id_tab);
        $controller_name = strtoupper($controller_name);
        $slug_prefix = 'ROLE_MOD_TAB_' . $controller_name . '_';

        foreach ($profiles as $profile) {
            $rights = [];
            $id_profile = intval($profile['id_profile']);

            if ($id_profile == 1) {
                $rights[] = 'CREATE';
                $rights[] = 'READ';
                $rights[] = 'UPDATE';
                $rights[] = 'DELETE';
            }

            foreach ($rights as $right) {
                $slug = $slug_prefix . $right;

                $this->connection->executeQuery(
                    'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'authorization_role`
                    (
                        `slug`
                    )
                    VALUES (
                        "' . $slug . '"
                    );'
                );

                $id_authorization_role = intval($this->connection->lastInsertId());
                if (!$id_authorization_role) {
                    $id_authorization_role = $this->connection->fetchColumn('
                        SELECT `id_authorization_role`
                        FROM `' . _DB_PREFIX_ . 'authorization_role`
                        WHERE `slug` = "' . $slug . '"
                    ');
                }

                $this->addSql(
                    'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`
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
     * Remove all access for an admin tab
     *
     * @param integer $id_tab ID of the tab
     * @return self
     */
    public function deleteAccess($id_tab)
    {
        $rights = [];

        $rights[] = 'CREATE';
        $rights[] = 'READ';
        $rights[] = 'UPDATE';
        $rights[] = 'DELETE';

        $controller_name = $this->getTabControllerName(intval($id_tab));
        $controller_name = strtoupper($controller_name);
        $slug_prefix = 'ROLE_MOD_TAB_' . $controller_name . '_';

        foreach ($rights as $right) {
            $slug = $slug_prefix . $right;

            $id_autorization_role = intval($this->connection->fetchColumn('
                SELECT `id_authorization_role`
                FROM `' . _DB_PREFIX_ . 'authorization_role`
                WHERE `slug` = "' . $slug . '"'
            ));

            $this->addSql('
                DELETE FROM `' . _DB_PREFIX_ . 'access`
                WHERE `id_authorization_role` = ' . $id_autorization_role . ';
            ');

            $this->addSql('
                DELETE FROM `' . _DB_PREFIX_ . 'authorization_role`
                WHERE `id_authorization_role` = ' . $id_autorization_role . ';
            ');
        }

        return $this;
    }
}