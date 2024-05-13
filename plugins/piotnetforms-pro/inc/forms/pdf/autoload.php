<?php

/**
 * This file is part of FPDI
 *
 * @package   psetasign\Fpdi
 * @copyright Copyright (c) 2020 psetasign GmbH & Co. KG (https://www.psetasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

// @phpstan-ignore-next-line
spl_autoload_register(function ($class) {
    if (strpos($class, 'psetasign\Fpdi\\') === 0) {
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 14)) . '.php';
        $fullpath = __DIR__ . DIRECTORY_SEPARATOR . $filename;
       	//echo "<pre>"; var_dump($fullpath); die('1123');

        if (is_file($fullpath)) {
            /** @noinspection PhpIncludeInspection */
            require_once $fullpath;
        }
    }
});
