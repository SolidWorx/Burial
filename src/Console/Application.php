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

namespace SolidWorx\Burial\Console;

use SolidWorx\Burial\Console\Command\Run;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public const VERSION = '0.2.0-dev';

    public function __construct()
    {
        parent::__construct('Burial', self::VERSION);

        $this->add(new Run());

        $this->setDefaultCommand(Run::COMMAND, true);
    }
}
