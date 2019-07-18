<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Burial project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Burial;

final class Tomb
{
    /** @var string */
    public $file;

    /** @var string */
    public $scope;

    /** @var string */
    public $function;

    /** @var int */
    public $startLine;

    /** @var int */
    public $endLine;

    public static function fromJson(string $json): Tomb
    {
        $data = json_decode(str_replace('\\', '\\\\', preg_replace('/[[:cntrl:]]/', '', $json)), true);

        $tomb = new self();
        $tomb->file = $data['location']['file'];
        $tomb->startLine = $data['location']['start'];
        $tomb->endLine = $data['location']['end'];
        $tomb->scope = $data['scope'] ?? '';
        $tomb->function = $data['function'];

        return $tomb;
    }
}
