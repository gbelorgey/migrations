<?php

namespace Doctrine\DBAL\Migrations\Traits;

trait ImageFormatTrait
{
    use ShopTrait;

    /**
     * Add an image format
     *
     * @param string  $family  Name of format's family.
     * @param array   $formats List of parameters for each formats.
     * @param integer $id_shop ID of shop to add format.
     *
     * @return self
     */
    protected function addImageFormat($family, $formats = array(), $id_shop = false)
    {
        $this->abortIf(empty($formats), 'Array of formats is required');

        if (!empty($id_shop)) {
            $shops = $this->getShopById($id_shop);
        } else {
            $shops = $this->getAllShops(true);
        }

        $check_params = $this->getImageFormatParams();
        foreach ($shops as $key => $shop) {
            foreach ($formats as $format) {
                $this->validateExpectations(
                    $check_params['expectations'],
                    $check_params['optional'],
                    $format
                );

                $name = '';
                $media = 'NULL';
                $from = 'NULL';
                $origin = 0;
                $required = 1;

                if (array_key_exists('name', $format)) {
                    $name =  $this->connection->quote($format['name']);
                }
                if (array_key_exists('media', $format)) {
                    $media =  $this->connection->quote($format['media']);
                }
                if (array_key_exists('from', $format)) {
                    $from = intval($format['from']);
                }
                if (array_key_exists('origin', $format)) {
                    $origin = $format['origin'];
                }
                if (array_key_exists('required', $format)) {
                    $required = $format['required'];
                }

                $this->connection->executeQuery(
                    'INSERT INTO `'._DB_PREFIX_.'image_format`
                        (
                            `id_shop`
                            , `family`
                            , `width`
                            , `height`
                            , `pattern`
                            , `ext`
                            , `name`
                            , `media`
                            , `from`
                            , `origin`
                            , `required`
                        )
                    VALUES
                        (
                            ' . intval($shop['id_shop']) . '
                            , ' . $this->connection->quote($family) . '
                            , ' . intval($format['width']) . '
                            , ' . intval($format['height']) . '
                            , ' . $this->connection->quote($format['pattern']) . '
                            , ' . $this->connection->quote($format['ext']) . '
                            , ' . $name . '
                            , ' . $media . '
                            , ' . $from . '
                            , ' . intval($origin) . '
                            , ' . intval($required) . '
                        )
                    ;'
                );

                $last_insert_id = (int) $this->connection->lastInsertId();
                if ($last_insert_id && array_key_exists('childs', $format)) {
                    $childs_formats = array();
                    foreach ($format['childs'] as $k => $child) {
                        $childs_formats[$k] = $child;
                        $childs_formats[$k]['from'] = $last_insert_id;
                        $childs_formats[$k]['name'] = null;
                    }

                    if (!empty($childs_formats)) {
                        $this->addImageFormat(
                            $family,
                            $childs_formats,
                            $shop['id_shop']
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Delete an image format
     *
     * @param string  $family  Name of format's family.
     * @param string  $pattern Name of specific pattern.
     * @param integer $id_shop ID of shop to add format.
     *
     * @return self
     */
    protected function deleteImageFormat($family, $pattern = false, $id_shop = false)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'image_format`'
                . ' WHERE TRUE'
                . ' AND `family` = ' . $this->connection->quote($family);

        if ($id_shop) {
            $sql .= ' AND `id_shop` = ' . intval($id_shop);
        }

        if ($pattern) {
            $sql .= ' AND `pattern` = ' . $this->connection->quote($pattern);
        }

        $this->addSql($sql);

        return $this;
    }

    /**
     * Get image format parameter optionnal and expectations.
     *
     * @return array
     */
    public function getImageFormatParams()
    {
        return [
            'expectations' => [
                'ext',
                'height',
                'pattern',
                'width'
            ],
            'optional' => [
                'childs',
                'from',
                'media',
                'name',
                'origin',
                'required'
            ]
        ];
    }
}
