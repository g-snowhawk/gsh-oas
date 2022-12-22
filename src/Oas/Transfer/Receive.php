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

use Gsnowhawk\Common\Lang;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    public const REDIRECT_MODE = 'oas.transfer.response';

    protected $saved_issue_date;
    protected $saved_page_number;

    /**
     * Save the data receive interface.
     */
    public function save(): bool
    {
        $redirect_type = 'redirect';
        $redirect_mode = (!empty($this->request->param('redirect_mode')))
            ? $this->request->param('redirect_mode')
            : self::REDIRECT_MODE;

        if ($referer = $this->request->param('script_referer')) {
            $redirect_mode = $referer;
            $redirect_type = 'referer';
        }

        $message_key = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];

        if (!parent::save()) {
            $message_key = 'FAILED_SAVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'edit'], null];
        } else {
            $redirect_mode .= sprintf('\:edit\#%s\:%s', $this->saved_issue_date, $this->saved_page_number);
            $response = [[$this, 'redirect'], [$redirect_mode, $redirect_type]];
        }

        $this->postReceived(Lang::translate($message_key), $status, $response, $options);
    }

    /**
     * Remove the data receive interface.
     */
    /*
    public function remove()
    {
        $redirect_type = 'redirect';
        $redirect_mode = (!empty($this->request->param('redirect_mode')))
            ? $this->request->param('redirect_mode')
            : self::REDIRECT_MODE;

        if ($referer = $this->request->param('script_referer')) {
            $redirect_mode = $referer;
            $redirect_type = 'referer';
        }

        $message_key = 'SUCCESS_REMOVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], [$redirect_mode, $redirect_type]];

        if (!parent::remove()) {
            $message_key = 'FAILED_REMOVE';
            $status = 1;
        }

        $this->postReceived(Lang::translate($message_key), $status, $response, $options);
    }
     */

    //public function suggestClient()
    //{
    //    $json_array = ['status' => 0];
    //    $ideographic_space = json_decode('"\u3000"');

    //    $keyword = str_replace([$ideographic_space,' '], '', $this->request->param('keyword'));

    //    $clients = $this->db->select(
    //        'company,fullname,zipcode,address1,address2,division',
    //        'receipt_to',
    //        "WHERE REPLACE(REPLACE(company,'$ideographic_space',' '),' ','') like ? collate utf8_unicode_ci",
    //        ["%$keyword%"]
    //    );

    //    if ($clients === false) {
    //        $json_array['status'] = 1;
    //        $json_array['message'] = 'Database Error: '.$this->db->error();
    //    } else {
    //        // TODO: Use to Template Engine
    //        $this->view->bind('clients', $clients);
    //        $json_array['source'] = $this->view->render('srm/receipt/suggest_client.tpl', true);
    //    }

    //    header('Content-type: application/json');
    //    echo json_encode($json_array);
    //    exit;
    //}

    public function suggestSummary()
    {
        $json_array = ['status' => 0];
        $ideographic_space = json_decode('"\u3000"');

        $keyword = str_replace([$ideographic_space,' '], '', $this->request->param('keyword') ?? '');

        $summary = $this->db->select(
            'summary',
            'transfer',
            "WHERE REPLACE(REPLACE(summary,'$ideographic_space',' '),' ','') like ? collate utf8_unicode_ci GROUP BY summary ORDER BY summary",
            ["%$keyword%"]
        );

        if ($summary === false) {
            $json_array['status'] = 1;
            $json_array['message'] = 'Database Error: '.$this->db->error();
        } else {
            // TODO: Use to Template Engine
            $this->view->bind('summary', $summary);
            $json_array['source'] = $this->view->render('oas/transfer/suggest_summary.tpl', true);
        }

        header('Content-type: application/json');
        echo json_encode($json_array);
        exit;
    }
}
