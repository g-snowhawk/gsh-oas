<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Taxation;

use DateTime;
use Gsnowhawk\Common\Http;
use Gsnowhawk\Common\Lang;

/**
 * Category management response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Socialinsurance extends \Gsnowhawk\Oas\Taxation
{
    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        $this->view->bind(
            'header',
            ['title' => Lang::translate('HEADER_TITLE'), 'id' => 'osa-taxation-socialinsurance', 'class' => 'taxation']
        );
    }

    /**
     * Default view.
     */
    public function defaultView(): void
    {
        $this->checkPermission('oas.taxation.read');

        $template_path = 'oas/taxation/social_insurance.tpl';
        $html_id = 'oas-taxation-socialinsurance';

        $sql = "SELECT year
                  FROM table::account_book
                 WHERE userkey = ? AND locked = '0'
                 GROUP BY year
                 ORDER BY year LIMIT 1";

        $years = [date('Y')];
        if (false !== $this->db->query($sql, [$this->uid])) {
            if ($unit = $this->db->fetch()) {
                $years = range($unit['year'], date('Y'));
                rsort($years);
            }
        } else {
            echo $this->db->error();
        }
        $this->view->bind('years', $years);

        $year = $this->session->param('si_year');
        if (empty($year)) {
            $year = $years[0];
        }

        $post = $this->request->param();
        if (!empty($post['year'])) {
            $year = $post['year'];
        }
        $this->view->bind('post', $post);

        $options = $years;
        $options[] = $this->uid;
        $list = $this->db->select(
            'id,year,colnumber,title,amount,note',
            'social_insurance',
            'WHERE year IN ('.implode(',', array_fill(0, count($years), '?')).') AND userkey = ? ORDER BY year DESC,colnumber',
            $options
        );
        foreach ($list as &$unit) {
            $unit['json'] = json_encode([
                'year' => $unit['year'],
                'colnumber' => $unit['colnumber'],
                'title' => $unit['title'],
                'amount' => $unit['amount'],
                'note' => $unit['note'],
            ], JSON_UNESCAPED_UNICODE);

            $unit['documents'] = [];
            if (preg_match('/a(\{.+\})/', $unit['note'] ?? '', $match)) {
                $json = json_decode($match[1]);
                $documents = $json->docid;
                $unit['documents'] = $documents;
            }
        }
        unset($unit);
        $this->view->bind('list', $list);

        $this->session->clear('si_year');

        $form = $this->view->param('form');
        $form['enctype'] = 'multipart/form-data';
        $this->view->bind('form', $form);

        $this->setHtmlId($html_id);
        $this->view->render($template_path);
    }

    public function save(): void
    {
        $data = $this->createSaveData(
            'social_insurance',
            $this->request->param(),
            ['id','userkey','modify_date']
        );
        $data['userkey'] = $this->uid;
        $year = $data['year'];

        $documents = [];
        if (preg_match('/a(\{.+\})/', $data['note'] ?? '', $match)) {
            $json = json_decode($match[1]);
            $documents = $json->docid;
        }

        $this->db->begin();

        $file = $this->request->FILES('file');
        $document_id = null;
        if (!empty($file)) {
            $date = new DateTime('now');
            if ($date->format('Y') !== $year) {
                $date->setDate((Int)$year, 12, 31);
            }
            $this->request->param('receipt_date', $date->format('Y-m-d'));
            $this->request->param('year', $date->format('Y'));
            $this->request->param('sender', $this->request->param('title'));
            $this->request->param('price', $this->request->param('amount'));
            $document_id = parent::saveAcceptedDocument('id');
        }

        if (false !== $document_id) {
            if (!is_null($document_id)) {
                $documents[] = (String)$document_id;
            }
            $documents = array_unique($documents, SORT_NUMERIC);
            $data['note'] = (!empty($documents)) ? 'a' . json_encode(['docid' => $documents]) : null;
            if (false !== $this->db->merge(
                'social_insurance',
                $data,
                ['id','userkey','modify_date'],
                'social_insurance_uk_1'
            )) {
                $sql = 'SELECT SUM(amount) AS amount,
                                MIN(LEFT(colnumber, 2)) AS colnumber
                          FROM table::social_insurance
                         WHERE year = ? AND userkey = ?
                         GROUP BY LEFT(colnumber, 2)';
                if (false !== $this->db->query($sql, [$year, $this->uid])) {
                    $data = [];
                    while ($unit = $this->db->fetch()) {
                        $key = 'col_' . $unit['colnumber'];
                        $data[$key] = $unit['amount'];
                    }
                    if (false !== $this->updateAccountBook($year, $data)) {
                        $this->db->commit();

                        $this->session->param('si_year', $this->request->param('year'));

                        $url = $this->app->systemURI().'?mode=oas.taxation.socialinsurance';
                        Http::redirect($url);
                    }
                }
            }
        }
        trigger_error($this->db->error());
        $this->db->rollback();
        $this->defaultView();
    }

    public function remove()
    {
        $id = $this->request->param('id');
        $table = 'social_insurance';
        $statement = 'userkey = ? AND id = ?';
        $replaces = [$this->uid, $id];
        $year = $this->db->get('year', $table, $statement, $replaces);

        $this->db->begin();
        if ($this->db->delete($table, $statement, $replaces)) {
            $sql = 'SELECT SUM(amount) AS amount,
                            MIN(LEFT(colnumber, 2)) AS colnumber
                      FROM table::social_insurance
                     WHERE year = ? AND userkey = ?
                     GROUP BY LEFT(colnumber, 2)';
            if (false !== $this->db->query($sql, [$year, $this->uid])) {
                $data = [];
                while ($unit = $this->db->fetch()) {
                    $key = 'col_' . $unit['colnumber'];
                    $data[$key] = $unit['amount'];
                }
                if (false !== $this->updateAccountBook($year, $data)) {
                    $this->db->commit();

                    $this->session->param('si_year', $year);

                    $url = $this->app->systemURI().'?mode=oas.taxation.socialinsurance';
                    Http::redirect($url);
                }
            }
        }
        trigger_error($this->db->error());
        $this->db->rollback();
        $this->defaultView();
    }

    private function saveAttachments($year): bool
    {
        $file = $this->request->FILES('file');

        // No file uploaded
        if (empty($file)) {
            return true;
        }

        $userkey = $this->uid;

        $checksum = hash_file('sha256', $file['tmp_name']);
        $mimetype = mime_content_type($file['tmp_name']);
        $extension = ($mimetype === 'message/rfc822') ? '.eml' : '.pdf';

        if ($this->db->exists('accepted_document', 'userkey = ? AND checksum = ?', [$userkey, $checksum])) {
            $this->app->err['vl_file'] = 128;
        }

        $date = new DateTime('now');
        if ($date->format('Y') !== $year) {
            $date->setDate((Int)$year, 12, 31);
        }

        $sequence = (int)$this->db->max(
            'sequence',
            'accepted_document',
            'userkey = ? AND year = ?',
            [$userkey, $date->format('Y')]
        ) + 1;

        $data = [
            'sequence' => $sequence,
            'userkey' => $userkey,
            'checksum' => $checksum,
            'mimetype' => $mimetype,
            'sender' => $this->request->param('title'),
            'category' => $this->request->param('category'),
            'source' => $this->request->param('source'),
            'receipt_date' => $date->format('Y-m-d'),
            'year' => $date->format('Y'),
            'price' => $this->request->param('amount'),
        ];

        // Save the meta data
        if (false === $this->db->insert('accepted_document', $data)) {
            trigger_error($this->db->error());

            return false;
        }

        $id = $this->db->lastInsertId();

        // Save the history
        $data = [
            'document_id' => $id,
            'type' => 'CREATE',
            'reason' => Lang::translate('CREATE_DOCUMENT'),
        ];
        if (false === $this->db->insert('accepted_history', $data)) {
            trigger_error($this->db->error());

            return false;
        }

        // Save the uploaded file
        $filepath = $this->getPdfPath(
            $date->format('Y'),
            'accepted_documents',
            "{$sequence}{$extension}"
        );

        $upload_dir = dirname($filepath);
        if (!file_exists($upload_dir) && false === @mkdir($upload_dir, 0700, true)) {
            trigger_error('Failed make a directory');

            return false;
        }

        if (false === move_uploaded_file($file['tmp_name'], $filepath)) {
            trigger_error('Does not save upload file');

            return false;
        }
        @chmod($filepath, 0444);

        return true;
    }
}
