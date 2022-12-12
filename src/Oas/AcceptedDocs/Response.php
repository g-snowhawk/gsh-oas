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

use Gsnowhawk\Oas\AcceptedDocs;

/**
 * Entry management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Response extends AcceptedDocs
{
    private $rows_per_page = 50;

    public function defaultView()
    {
        $this->checkPermission('oas.accepteddocs.read');

        $current_page = (!empty($this->request->param('p'))) ? $this->request->param('p') : 1;
        $this->session->param(self::PAGE_KEY, $current_page);

        $options = [$this->uid];
        $conditions = ['userkey = ?'];
        $search_options = $this->session->param(self::SEARCH_OPTIONS_KEY) ?: ['andor' => 'AND'];

        $between_receipt_date = [];
        if (!empty($search_options['receipt_date_start'])) {
            $between_receipt_date[] = 'receipt_date >= ?';
            $options[] = date('Y-m-d', strtotime($search_options['receipt_date_start']));
        }
        if (!empty($search_options['receipt_date_end'])) {
            $between_receipt_date[] = 'receipt_date <= ?';
            $options[] = date('Y-m-d', strtotime($search_options['receipt_date_end']));
        }
        if (!empty($between_receipt_date)) {
            $conditions[] = '(' . implode(' AND ', $between_receipt_date) . ')';
        }

        $between_price = [];
        if (!empty($search_options['price_min'])) {
            $between_price[] = 'price >= ?';
            $options[] = $search_options['price_min'];
        }
        if (!empty($search_options['price_max'])) {
            $between_price[] = 'price <= ?';
            $options[] = $search_options['price_max'];
        }
        if (!empty($between_price)) {
            $conditions[] = '(' . implode(' AND ', $between_price) . ')';
        }

        if (!empty($search_options['category'])) {
            $conditions[] = 'category = ?';
            $options[] = $search_options['category'];
        }

        $andor = (!empty($search_options['andor'])) ? $search_options['andor'] : 'AND';

        $query_string = $this->getSearchCondition();
        if (!empty($query_string)) {
            $keywords = explode(' ', $query_string);
            $filters = [];
            foreach ($keywords as $keyword) {
                $filters[] = "%{$keyword}%";
            }
            $conditions[] = implode(" {$andor} ", array_fill(0, count($filters), 'sender LIKE ?'));
            $options = array_merge($options, $filters);

            $this->view->bind('queryString', $query_string);
            $this->session->param(parent::QUERY_STRING_KEY, $query_string);
        }

        $statement = implode(' AND ', $conditions);

        // Pagenation
        $rows_per_page = (empty($this->session->param('rows_per_page_accepteddocs_list')))
            ? $this->rows_per_page
            : (int)$this->session->param('rows_per_page_accepteddocs_list');
        $total_count = $this->db->count('accepted_document', $statement, $options);
        $pager = clone $this->pager;
        $pager->init($total_count, $rows_per_page);
        $total = $pager->total();
        if ($current_page > $total) {
            $current_page = $total;
        }
        $pager->setCurrentPage($current_page);
        $pager->setLinkFormat($this->app->systemURI().'?mode='.parent::DEFAULT_MODE.'&p=%d');
        $this->view->bind('pager', $pager);
        $offset_list = $rows_per_page * ($current_page - 1);
        $statement .= " LIMIT $offset_list,$rows_per_page";

        $docs = $this->db->select('*', 'accepted_document', 'WHERE '.$statement, $options);
        $this->view->bind('docs', $docs);

        $template_path = 'oas/accepteddocs/default.tpl';
        $html_id = 'oas-accepteddocs-default';

        $this->setHtmlId($html_id);
        $this->view->render($template_path);
    }

    /**
     * Response of file upload form
     *
     * @return void
     */
    public function addFile(): void
    {
        $rel = $this->request->param('rel');
        $this->view->bind('rel', $rel);

        if (!empty($rel) && preg_match('/^[PRT](\d{4})(\d{2})(\d{2})\.\d{2}+$/', $rel, $match)) {
            $post = [
                'receipt_date' => sprintf('%d-%d-%d', $match[1], $match[2], $match[3]),
            ];
            $this->view->bind('post', $post);
        }

        $this->view->bind('err', $this->app->err);
        $response = $this->view->render('oas/accepteddocs/addfile.tpl', true);

        $json = [
            'status' => 200,
            'response' => $response,
            'finally' => 'setRel',
        ];
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    /**
     * Response of search option form
     *
     * @return void
     */
    public function searchOptions(): void
    {
        $search_options = $this->session->param(self::SEARCH_OPTIONS_KEY) ?: ['andor' => 'AND'];
        $this->view->bind('post', $search_options);

        $query_string = $this->getSearchCondition();
        $this->view->bind('queryString', $query_string);

        $response = $this->view->render('oas/accepteddocs/search_options.tpl', true);
        $json = [
            'status' => 200,
            'response' => $response,
        ];
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($json);
        exit;
    }
}
