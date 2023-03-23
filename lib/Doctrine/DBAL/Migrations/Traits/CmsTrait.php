<?php

namespace Doctrine\DBAL\Migrations\Traits;

use Language; // @todo Mettre en place une classe Language

trait CmsTrait
{
    /**
     * Create a cms page
     *
     * @param integer $id_cms_category
     * @param array $langs
     * @param bool $active
     * @param bool $active
     * @param bool $indexation
     * @return int
     */
    protected function addCms($id_cms_category, $langs = array(), $active = true, $indexation = true)
    {
        $this->abortIf(empty($id_cms_category), 'ID Cms category parent is required');

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms`
                    (
                        `id_cms_category`
                        , `position`
                        , `active`
                        , `indexation`
                    )
                SELECT
                    `id_cms_category`
                    , MAX(`position`) + 1
                    , ' . ($active ? 1 : 0) . '
                    , ' . ($indexation ? 1 : 0) . '
                FROM `' . _DB_PREFIX_ . 'cms`
                WHERE TRUE
                AND `id_cms_category` = ' . intval($id_cms_category) . '
                ;';

        $this->connection->executeQuery($sql);

        $id_cms = (int) $this->connection->lastInsertId();

        // @todo Passer par la classe Shop
        $shops = $this->connection->fetchAll(
            'SELECT * '
            . 'FROM `' . _DB_PREFIX_ . 'shop` '
            . 'WHERE `active`'
        );

        if (!empty($shops)) {
            foreach ($shops as $shop) {
                $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_shop`
                        VALUES (' . intval($id_cms) . ', ' . intval($shop['id_shop']) . ');';

                $this->addSql($sql);

                foreach ($langs as $code_iso => $value) {
                    $id_lang = Language::getIdByIso($code_iso);

                    if (empty($id_lang)) {
                        continue;
                    }

                    $meta_title = '';
                    $meta_description = '';
                    $meta_keywords = '';
                    $link_rewrite = '';
                    $content = '';

                    if (!empty($value['meta_title'])) {
                        $meta_title = $value['meta_title'];
                    }

                    if (!empty($value['meta_description'])) {
                        $meta_description = $value['meta_description'];
                    }

                    if (!empty($value['meta_keywords'])) {
                        $meta_keywords = $value['meta_keywords'];
                    }

                    if (!empty($value['link_rewrite'])) {
                        $link_rewrite = $value['link_rewrite'];
                    }

                    if (!empty($value['content'])) {
                        $content = $value['content'];
                    }

                    $this->addSql(
                        'INSERT INTO `'._DB_PREFIX_.'cms_lang`
                            (
                                `id_cms`
                                , `id_lang`
                                , `id_shop`
                                , `meta_title`
                                , `meta_description`
                                , `meta_keywords`
                                , `link_rewrite`
                                , `content`
                            )
                        VALUES (
                            ' . (int)$id_cms . '
                            , ' . (int)$id_lang . '
                            , ' . (int)$shop['id_shop'] . '
                            , ' . $this->connection->quote($meta_title) . '
                            , ' . $this->connection->quote($meta_description) . '
                            , ' . $this->connection->quote($meta_keywords) . '
                            , ' . $this->connection->quote($link_rewrite) . '
                            , ' . $this->connection->quote($content, '-h') . '
                        );'
                    );
                }
            }
        }

        return $id_cms;
    }

    protected function deleteCms($id_cms)
    {
        $this->abortIf(empty($id_cms), 'ID Cms is required for deletion');

        $this->addSql(
            'DELETE FROM `' . _DB_PREFIX_ . 'cms` ' .
            'WHERE `id_cms` = ' . (int)$id_cms . ';'
        );

        $this->addSql(
            'DELETE FROM `' . _DB_PREFIX_ . 'cms_lang` ' .
            'WHERE `id_cms` = ' . (int)$id_cms . ';'
        );
    }

    /**
     * Get cms ID by meta title
     *
     * @param string $meta_title Cms's meta title
     * @return integer
     */
    protected function getCmsId($meta_title)
    {
        $this->abortIf(empty($meta_title), 'Meta title cms is required');

        $sql = 'SELECT `id_cms`
            FROM ' . _DB_PREFIX_ . 'cms_lang
            WHERE `meta_title` = ' . $this->connection->quote($meta_title) . ';';

        return (int)$this->connection->fetchColumn($sql);
    }

    /**
     * Get max position for an ID cms parent
     *
     * @param int $id_cms_category ID Cms parent
     * @return integer
     */
    protected function getCmsMaxPosition($id_cms_category)
    {
        $this->abortIf(empty($id_cms_category), 'ID Cms parent is required to get max position');

        return (int) $this->connection->fetchColumn(
            'SELECT MAX(`position`) '
            . 'FROM `'._DB_PREFIX_.'cms` '
            . 'WHERE `id_cms_category` =' . (int)$id_cms_category
        );
    }
}
