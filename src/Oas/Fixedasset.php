<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas;


use Gsnowhawk\Pdf;

/**
 * Category management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Fixedasset extends Taxation
{
    const MEMVALUE = 1;

    /**
     * Using common accessor methods
     */
    use \Gsnowhawk\Accessor;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        $paths = $this->view->getPaths();
        $this->pdf = new Pdf($paths);
    }

    protected function save()
    {
        $id = $this->request->POST('id');
        $check_type = (empty($id)) ? 'create' : 'update';
        $this->checkPermission('oas.fixedasset.'.$check_type);

        $post = $this->request->post();

        $table = 'fixed_assets';
        $skip = ['id', 'userkey', 'modify_date'];

        $valid = [];
        $valid[] = ['vl_item', 'item', 'empty'];
        $valid[] = ['vl_title', 'title', 'empty'];
        $valid[] = ['vl_quantity', 'quantity', 'blank'];
        $valid[] = ['vl_acquire', 'acquire', 'empty'];
        $valid[] = ['vl_price', 'price', 'empty'];
        $valid[] = ['vl_location', 'location', 'empty'];
        $valid[] = ['vl_durability', 'durability', 'empty'];
        $valid[] = ['vl_depreciate_type', 'depreciate_type', 'empty'];
        $valid[] = ['vl_depreciate_rate', 'depreciate_rate', 'blank'];
        //$valid[] = ['vl_residual_value', 'residual_value', 'empty'];
        $valid[] = ['vl_official_ratio', 'official_ratio', 'empty'];

        if (!$this->validate($valid)) {
            return false;
        }

        $this->db->begin();

        $fields = $this->db->getFields($this->db->TABLE($table));
        $save = [];
        $raw = [];
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (isset($post[$field])) {
                $save[$field] = $post[$field];
            }
        }

        if (empty($post['id'])) {
            $save['userkey'] = $this->uid;
            $result = $this->db->insert($table, $save, $raw);
        } else {
            $result = $this->db->update($table, $save, 'id = ?', [$post['id']], $raw);
        }
        if ($result !== false) {
            return $this->db->commit();
        }
        trigger_error($this->db->error());
        $this->db->rollback();

        return false;
    }

    protected function saveDetail()
    {
        $id = $this->request->POST('id');
        $this->checkPermission('oas.fixedasset.update');

        $post = $this->request->post();

        $table = 'fixed_assets_detail';

        $valid = [];
        $valid[] = ['vl_transfer_date', 'transfer_date', 'empty'];
        $valid[] = ['vl_summary', 'summary', 'empty'];
        $valid[] = ['vl_change_quantity', 'change_quantity', 'empty'];
        $valid[] = ['vl_change_price', 'change_price', 'empty'];

        if (!$this->validate($valid)) {
            return false;
        }

        $this->db->begin();

        $save = [];
        $save['id'] = $post['id'];

        $date = strtotime($post['transfer_date']);
        $save['year'] = date('Y', $date);
        $save['month'] = date('n', $date);
        $save['date'] = date('j', $date);
        $save['summary'] = $post['summary'];
        $save['change_quantity'] = $post['change_quantity'];
        $save['change_price'] = $post['change_price'];
        $save['note'] = $post['note'];

        $result = $this->db->insert($table, $save);

        if ($result !== false) {
            return $this->db->commit();
        }

        trigger_error($this->db->error());
        $this->db->rollback();

        return false;
    }

    public static function depreciate(array $result, bool $lda, int $target_year, bool $istotal = true)
    {
        $total = 0;
        $acquire = intval(date('Y', strtotime($result['acquire'])));
        $durability = intval($result['durability']);
        $limit = $acquire + $durability;
        $end = min($limit, $target_year);

        if ($lda) {
            if ($limit <= $target_year) return 0;
            $surplus = $result['price'] % $durability;
            $depreciate = ($result['price'] - $surplus) / $durability;

            for ($y = $acquire; $y <= $end; $y++) {
                if ($y === $acquire) {
                    $subtotal = $depreciate + $surplus;
                } else {
                    $subtotal = $depreciate;
                }
                if (!$istotal) {
                    if ($limit >= $target_year && $y === $target_year) return $subtotal;
                }
                $total += $subtotal;
            }
        } else {
            for ($y = $acquire; $y <= $end; $y++) {
                if (isset($result['ido']['month'])) {
                    $months = $result['ido']['month'];
                } elseif ($y === $acquire) {
                    $months = 13 - intval(date('n', strtotime($result['acquire'])));
                } elseif ($y === $limit) {
                    $months = 12 - (13 - intval(date('n', strtotime($result['acquire']))));
                } else {
                    $months = ($y > $limit) ? 0 : 12;
                }
                $subtotal = ceil($result['price'] * $result['depreciate_rate'] * ($months / 12));
                if (!$istotal) {
                    if ($limit >= $target_year && $y === $target_year) return $subtotal;
                }
                $total += $subtotal;
            }
            if ($total >= $result['price']) {
                $total = $result['price'] - self::MEMVALUE;
            }
        }

        return $total;
    }
}
