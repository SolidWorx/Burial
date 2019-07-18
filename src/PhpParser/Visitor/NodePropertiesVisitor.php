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

namespace SolidWorx\Burial\PhpParser\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;

class NodePropertiesVisitor extends NodeVisitorAbstract
{
    /** @var string|null */
    private $className;

    /** @var ClassLike|null */
    private $classNode;

    public function beforeTraverse(array $nodes): void
    {
        $this->classNode = null;
        $this->className = null;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Class_ && $this->isAnonymousClass($node)) {
            return null;
        }

        if ($node instanceof ClassLike) {
            $this->classNode = $node;
            $this->className = (string) $node->namespacedName;
        }

        $node->setAttribute('classNode', $this->classNode);
        $node->setAttribute('className', $this->className);

        return $node;
    }

    private function isAnonymousClass(Class_ $class): bool
    {
        return $class->isAnonymous() || $class->name === null || 0 === strncmp((string) $class->name, 'AnonymousClass', 14);
    }
}
