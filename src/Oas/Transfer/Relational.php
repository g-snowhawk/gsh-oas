<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Transfer;

use Gsnowhawk\Oas\Transfer;
use Exception;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Relational extends Transfer
{
    protected $saved_issue_date;
    protected $saved_page_number;

    private $caller;

    /**
     * Object constructor
     *
     * - Validate the caller has Gsnowhawk\PackageInterface
     *   If caller has no interface then throw Exception
     */
    public function __construct()
    {
        $params = func_get_args();
        $this->caller = array_shift($params);

        if (false === is_subclass_of($this->caller, 'Gsnowhawk\\PackageInterface')) {
            throw new Exception('No match application type');
        }

        call_user_func_array('parent::__construct', $params);
    }

    /**
     * Object destructor
     *
     * - Rewind current application to the caller
     */
    public function __destruct()
    {
        $this->caller->setCurrentApplication();
    }

    /**
     * Save the data receive interface.
     */
    public function save(): bool
    {
        return parent::save(true);
    }
}
