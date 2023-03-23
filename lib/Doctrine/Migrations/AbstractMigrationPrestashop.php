<?php

namespace Doctrine\Migrations;

use Doctrine\DBAL\Migrations\Traits\AccessTrait;
use Doctrine\DBAL\Migrations\Traits\CarrierTrait;
use Doctrine\DBAL\Migrations\Traits\ConfigurationTrait;
use Doctrine\DBAL\Migrations\Traits\CmsTrait;
use Doctrine\DBAL\Migrations\Traits\DbToolsTrait;
use Doctrine\DBAL\Migrations\Traits\HookTrait;
use Doctrine\DBAL\Migrations\Traits\ImageFormatTrait;
use Doctrine\DBAL\Migrations\Traits\MetaTrait;
use Doctrine\DBAL\Migrations\Traits\ModuleTrait;
use Doctrine\DBAL\Migrations\Traits\ShopTrait;
use Doctrine\DBAL\Migrations\Traits\TabTrait;
use Doctrine\Migrations\AbstractMigration;

abstract class AbstractMigrationPrestashop extends AbstractMigration
{
    protected $params_expectations;
    protected $params_optional;

    use AccessTrait {
        AccessTrait::addAccess insteadof TabTrait;
        AccessTrait::deleteAccess insteadof TabTrait;
    }
    use CarrierTrait;
    use ConfigurationTrait;
    use CmsTrait;
    use DbToolsTrait;
    use HookTrait;
    use ImageFormatTrait;
    use MetaTrait;
    use ModuleTrait;
    use ShopTrait {
        ShopTrait::getAllShops insteadof ImageFormatTrait;
        ShopTrait::getShopById insteadof ImageFormatTrait;
    }
    use TabTrait;

    protected function validateExpectations(array $params_expectations, array $params_optional, array $data)
    {
        if (array_keys($data) != $params_expectations) {
            $missing = array_diff(
                $params_expectations,
                array_keys($data),
                $params_optional
            );

            if (count($missing) > 0) {
                $errors = array_values(
                    array_map(
                        function ($field) {
                            return 'Missing param ' . $field;
                        },
                        $missing
                    )
                );
            } else {
                $unknown = array_diff(
                    array_keys($data),
                    $params_expectations,
                    $params_optional
                );

                $errors = array_values(
                    array_map(
                        function ($field) {
                            return 'Unexpected param ' . $field;
                        },
                        $unknown
                    )
                );
            }

            if (count($errors) > 0) {
                $this->warnIf(
                    true,
                    PHP_EOL . PHP_EOL .
                    'Params expectations : ' . json_encode($params_expectations) .
                    PHP_EOL .
                    'Params optionals : ' . json_encode($params_optional) .
                    PHP_EOL
                );

                $this->abortIf(true, current($errors));

                return false;
            }
        }

        return true;
    }
}
