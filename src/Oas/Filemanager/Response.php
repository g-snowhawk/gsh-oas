<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2018 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Filemanager;

/**
 * Entry management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Response extends \Gsnowhawk\Filemanager
{
    /**
     * Object Constructor.
     */
    public function __construct()
    {
        call_user_func_array(parent::class.'::__construct', func_get_args());

        $root = $this->privateSavePath();
        $conf_file = $root . '/oas_config.json';
        if (file_exists($conf_file)) {
            $config = json_decode(file_get_contents($conf_file));
            if ($config->private_save_path) {
                $root = $this->privateSavePath($config->private_save_path);
            }
        }

        parent::setRootDirectory($root);
        parent::setBaseUrl($config->baseurl ?? null);
    }

    public function defaultView()
    {
        parent::explorer();
    }

    public function addFolder()
    {
        parent::addFolder();
    }

    public function addFile()
    {
        parent::addFile();
    }

    public function childDirectories($directory, $parent)
    {
        return parent::fileList($directory, $parent, 'directory');
    }

    public function childFiles($directory, $parent)
    {
        return parent::fileList($directory, $parent, 'file');
    }

    public function download($path)
    {
        parent::download($path);
    }
}
