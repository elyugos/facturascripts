<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * Element of the third level of the accounting plan.
 * It is related to a single fiscal year and epigraph,
 * but it can be related to many subaccounts.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Cuenta extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Identifier of the special account.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Code of the exercise of this account.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var bool
     */
    private static $disableAditionTest = false;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Parent account code
     *
     * @var string
     */
    public $parent_codcuenta;

    /**
     * Identifier of the parent account
     *
     * @var integer
     */
    public $parent_idcuenta;

    /**
     * 
     * @return Cuenta
     */
    public function getParent()
    {
        $parent = new Cuenta();
        $parent->loadFromCode($this->parent_idcuenta);
        return $parent;
    }

    public function disableAditionalTest()
    {
        self::$disableAditionTest = true;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// force the parents tables
        new CuentaEspecial();
        new Ejercicio();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcuenta';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->descripcion = Utils::noHtml($this->descripcion);
        if (empty($this->descripcion)) {
            self::$miniLog->alert(self::$i18n->trans('account-data-missing'));
            return false;
        }

        /// uncomplete parent account data?
        if (empty($this->parent_codcuenta) && !empty($this->parent_idcuenta)) {
            $this->completeParentData();
        }

        if (!empty($this->parent_idcuenta) && !self::$disableAditionTest) {
            $parent = $this->getParent();
            if ($parent->codejercicio != $this->codejercicio || $parent->idcuenta == $this->idcuenta) {
                self::$miniLog->alert(self::$i18n->trans('account-parent-error'));
                return false;
            }
        }

        return parent::test();
    }

    /**
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return parent::url($type, 'ListCuenta?activetab=List');
    }

    /**
     * 
     * @return bool
     */
    private function completeParentData()
    {
        $parent = $this->getParent();
        if ($parent->exists() && $parent->codejercicio == $this->codejercicio && $parent->idcuenta != $this->idcuenta) {
            $this->parent_codcuenta = $parent->codcuenta;
            return true;
        }

        $this->parent_codcuenta = null;
        $this->parent_idcuenta = null;
        return false;
    }
}
