<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait MetaTrait
{
    protected function addMeta($name, $langs = array())
    {
        $this->connection->executeQuery(
            'INSERT INTO `'._DB_PREFIX_.'meta`
                (
                    `page`,
                    `configurable`
                )
            VALUES (
                ' . $this->connection->quote($name) . ',
                1
            );'
        );

        $id_meta = (int) $this->connection->lastInsertId();
        $shops = $this->connection->fetchAll('SELECT `id_shop` FROM `'._DB_PREFIX_.'shop`');
        foreach ($shops as $shop) {
            foreach ($langs as $code_iso => $value) {
                $id_lang = \Language::getIdByIso($code_iso);
                $title = (!empty($value['title'])) ? $value['title'] : '';
                $description = (!empty($value['description'])) ? $value['description'] : '';
                $keywords = (!empty($value['keywords'])) ? $value['keywords'] : '';
                $url_rewrite = (!empty($value['url_rewrite'])) ? $value['url_rewrite'] : '';

                $this->addSql(
                    'INSERT INTO `'._DB_PREFIX_.'meta_lang`
                        (
                            `id_meta`,
                            `id_shop`,
                            `id_lang`,
                            `title`,
                            `description`,
                            `keywords`,
                            `url_rewrite`
                        )
                    VALUES (
                        '.(int)$id_meta.',
                        '.(int)$shop['id_shop'].',
                        '.(int)$id_lang.',
                        ' . $this->connection->quote($title) . ',
                        ' . $this->connection->quote($description) . ',
                        ' . $this->connection->quote($keywords) . ',
                        ' . $this->connection->quote($url_rewrite) . '
                    );'
                );
            }
        }
    }

    protected function deleteMeta($name)
    {
        $id_meta = $this->connection->fetchColumn(
            'SELECT `id_meta`
            FROM `'._DB_PREFIX_.'meta`
            WHERE `page` = ' . $this->connection->quote($name) . ';'
        );

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'meta`
            WHERE `id_meta` = '.(int)$id_meta.';'
        );

        $this->addSql(
            'DELETE FROM `'._DB_PREFIX_.'meta_lang`
            WHERE `id_meta` = '.(int)$id_meta.';'
        );
    }
}
