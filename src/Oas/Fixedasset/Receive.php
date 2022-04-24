<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Fixedasset;

use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    /**
     * Save the data receive interface.
     */
    public function save()
    {
        if (parent::save()) {
            $this->session->param('messages', Lang::translate('SUCCESS_SAVED'));
            $url = $this->app->systemURI().'?mode=oas.fixedasset.response';
            Http::redirect($url);
        }
        $this->edit();
    }

    /**
     * Save the detail data receive interface.
     */
    public function saveDetail()
    {
        if (parent::saveDetail()) {
            $this->session->param('messages', Lang::translate('SUCCESS_SAVED'));
            $url = $this->app->systemURI().'?mode=oas.fixedasset.response';
            Http::redirect($url);
        }
        $this->edit();
    }

    /**
     * Remove the data receive interface.
     */
    public function remove()
    {
        if (parent::remove()) {
            $this->session->param('messages', Lang::translate('SUCCESS_REMOVED'));
        }
        Http::redirect($this->app->systemURI().'?mode=oas.fixedasset.response');
    }
}
