<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * Element of the third level of the accounting plan.
 * It is related to a single fiscal year and epigraph,
 * but it can be related to many subaccounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Cuenta extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Identificacion de la empresa
     *
     * @var int
     */
    public $idempresa;

    /**
     * Code of the exercise of this account.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    public $parent_id;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_cuentas';
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
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// force the parents tables
        new Empresa();
        new Ejercicio();

        return '';
    }

    /**
     * Returns all sub-accounts in the account.
     *
     * @return Subcuenta[]
     */
    public function getSubcuentas()
    {
        $subcuenta = new Subcuenta();

        return $subcuenta->all([new DataBaseWhere('idcuenta', $this->idcuenta)]);
    }

    /**
     * Returns the exercise.
     *
     * @return bool|mixed
     */
    public function getEjercicio()
    {
        $eje = new Ejercicio();

        return $eje->get($this->codejercicio);
    }

    /**
     * You get the first selected account.
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|Cuenta
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codcuenta = ' . self::$dataBase->var2str($cod) .
            ' AND codejercicio = ' . self::$dataBase->var2str($codejercicio) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Gets the first selected special account.
     *
     * @param int    $idcuesp
     * @param string $codejercicio
     *
     * @return bool|Cuenta
     */
    public function getCuentaesp($idcuesp, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE idcuentaesp = ' . self::$dataBase->var2str($idcuesp) .
            ' AND codejercicio = ' . self::$dataBase->var2str($codejercicio) . ' ORDER BY codcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = Utils::noHtml($this->descripcion);

        if (strlen($this->codcuenta) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }
        self::$miniLog->alert(self::$i18n->trans('account-data-missing'));

        return false;
    }

    /**
     * Returns all the accounts of the epigraph.
     *
     * @param int $idepi
     *
     * @return self[]
     */
    public function fullFromEpigrafe($idepi)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE idepigrafe = ' . self::$dataBase->var2str($idepi)
            . ' ORDER BY codcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new self($c);
            }
        }

        return $cuenlist;
    }

    /**
     * Returns all the accounts for the indicated offset.
     *
     * @param string $codejercicio
     * @param int    $offset
     *
     * @return self[]
     */
    public function allFromEjercicio($codejercicio, $offset = 0)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codejercicio = ' . self::$dataBase->var2str($codejercicio) .
            ' ORDER BY codcuenta ASC';

        $data = self::$dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new self($c);
            }
        }

        return $cuenlist;
    }

    /**
     * Returns all accounts for the year.
     *
     * @param string $codejercicio
     *
     * @return self[]
     */
    public function fullFromEjercicio($codejercicio)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codejercicio = ' . self::$dataBase->var2str($codejercicio)
            . ' ORDER BY codcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new self($c);
            }
        }

        return $cuenlist;
    }

    /**
     * Returns all special accounts for the year.
     *
     * @param int    $idcuesp
     * @param string $codejercicio
     *
     * @return self[]
     */
    public function allFromCuentaesp($idcuesp, $codejercicio)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE idcuentaesp = ' . self::$dataBase->var2str($idcuesp)
            . ' AND codejercicio = ' . self::$dataBase->var2str($codejercicio) . ' ORDER BY codcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $cue) {
                $cuenlist[] = new self($cue);
            }
        }

        return $cuenlist;
    }

    /**
     * Returns a new account for the exercise.
     *
     * @param int $sumaCodigo
     *
     * @return bool|Subcuenta
     */
    public function newSubcuenta($sumaCodigo)
    {
        $ejercicioModel = new Ejercicio();
        $ejercicio = $ejercicioModel->get($this->codejercicio);
        if ($ejercicio !== false) {
            /// new codsubcuenta
            $codsubcuenta = (string) (sprintf('%-0' . $ejercicio->longsubcuenta . 's', $this->codcuenta) + $sumaCodigo);
            $subcuentaModel = new Subcuenta();

            $newSubcuenta = $subcuentaModel->getByCodigo($codsubcuenta, $this->codejercicio);
            if ($newSubcuenta === flase) {
                $newSubcuenta = new Subcuenta();
                $newSubcuenta->codcuenta = $this->codcuenta;
                $newSubcuenta->idcuenta = $this->idcuenta;
                $newSubcuenta->codejercicio = $this->codejercicio;
                $newSubcuenta->codsubcuenta = $codsubcuenta;
            }

            return $newSubcuenta;
        }

        return false;
    }
}
