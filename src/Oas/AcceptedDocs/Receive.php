<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2022 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\AcceptedDocs;

use ErrorException;
use Gsnowhawk\Common\Lang;
use Gsnowhawk\Core\Error;
use PhpMimeMailParser\Parser;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    public const REDIRECT_MODE = 'oas.accepted-docs.response';

    public function displayDocument(): void
    {
        $id = $this->request->param('id');
        $data = $this->db->get(
            'sequence,checksum,mimetype,receipt_date',
            'accepted_document',
            'id = ? AND userkey = ?',
            [$id, $this->uid]
        );

        $extension = ($data['mimetype'] === 'message/rfc822') ? '.eml' : '.pdf';
        $filepath = $this->getPdfPath(
            date('Y', strtotime($data['receipt_date'])),
            'accepted_documents',
            "{$data['sequence']}{$extension}"
        );

        if (file_exists($filepath)) {
            if ($data['checksum'] !== hash_file('sha256', $filepath)) {
                throw new ErrorException('This file that could have been falsified');
            } else {
                if ($extension === '.eml') {
                    if (function_exists('mailparse_msg_parse_file')) {
                        $parser = new Parser();
                        $parser->setStream(fopen($filepath, 'r'));
                        $message_body = $parser->getMessageBody('html');
                        if (empty($message_body)) {
                            header('Content-Type: text/plain');
                            echo $parser->getMessageBody('text');
                        } else {
                            echo $message_body;
                        }
                    } else {
                        header('Content-Type: message/rfc822');
                        header('Content-Disposition: inline; filename="' . "{$data['sequence']}{$extension}" . '"');
                        readfile($filepath);
                    }
                    exit;
                } else {
                    parent::responsePDF($filepath);
                }
            }
        } else {
            parent::pageNotFound($this->app);
        }
    }

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
                [[$this->view, 'bind'], ['post', $this->request->param()]],
            ];
            $response = [[$this, 'addFile'], null];
        } else {
            //$response = [[$this, 'redirect'], [$redirect_mode, $redirect_type]];
            $response = [[$this, 'didSetSearchOptions'], ['created']];
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

    public function suggestSender()
    {
        $json_array = ['status' => 0];
        $ideographic_space = json_decode('"\u3000"');

        $keyword = str_replace([$ideographic_space,' '], '', $this->request->param('keyword'));

        $senders = $this->db->select(
            'sender',
            'accepted_document',
            "WHERE REPLACE(REPLACE(sender,'$ideographic_space',' '),' ','') like ? collate utf8_unicode_ci",
            ["%$keyword%"]
        );

        if ($senders === false) {
            $json_array['status'] = 1;
            $json_array['message'] = 'Database Error: '.$this->db->error();
        } else {
            // TODO: Use to Template Engine
            $this->view->bind('senders', $senders);
            $json_array['source'] = $this->view->render('oas/accepteddocs/suggest_sender.tpl', true);
        }

        header('Content-type: application/json');
        echo json_encode($json_array);
        exit;
    }

    public function saveSearchOptions()
    {
        $args = 'save';
        if ($this->request->param('submitter') !== 's1_clear') {
            $andor = $this->request->param('andor');
            if ($andor !== 'AND' && $andor !== 'OR') {
                $andor = 'AND';
            }
            $search_options = [
                'receipt_date_start' => $this->request->param('receipt_date_start'),
                'receipt_date_end' => $this->request->param('receipt_date_end'),
                'price_min' => $this->request->param('price_min'),
                'price_max' => $this->request->param('price_max'),
                'category' => $this->request->param('category'),
                'andor' => $andor,
            ];
            $this->session->param(parent::SEARCH_OPTIONS_KEY, $search_options);

            $query_string = $this->getSearchCondition('search_query');
            $this->session->param(parent::QUERY_STRING_KEY, $query_string);
        } else {
            $this->session->clear(parent::SEARCH_OPTIONS_KEY);
            $this->session->clear(parent::QUERY_STRING_KEY);
            $args = 'clear';
        }

        $response = [[$this, 'didSetSearchOptions'], [$args]];
        $this->postReceived('', 0, $response, []);
    }

    public function didSetSearchOptions($args)
    {
        return ['type' => 'callback', 'source' => 'acceptedDocumentCloseSubForm', 'arguments' => [$args]];
    }
}
