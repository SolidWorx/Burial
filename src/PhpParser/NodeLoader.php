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

namespace SolidWorx\Burial\PhpParser;

use Composer\Autoload\ClassLoader;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;

final class NodeLoader
{
    /** @var Parser */
    private $parser;

    /** @var ClassLoader|null */
    private $loader;

    /** @var Node[] */
    private $nodes = [];

    public function __construct(Parser $parser, ?ClassLoader $loader)
    {
        $this->parser = $parser;
        $this->loader = $loader;
    }

    /**
     * @return Node\Stmt\Class_|Node|null
     */
    public function findClassNode(string $class): ?Node
    {
        return $this->findNode(function (Node $node) use ($class) {
            return $node instanceof Node\Stmt\Class_ && (string) $node->namespacedName === $class;
        }, $class);
    }

    /**
     * @return Node\Stmt\Interface_|Node|null
     */
    public function findInterfaceNode(string $class): ?Node
    {
        return $this->findNode(function (Node $node) use ($class) {
            return $node instanceof Node\Stmt\Interface_ && (string) $node->namespacedName === $class;
        }, $class);
    }

    private function findNode(callable $filter, string $class): ?Node
    {
        if (null === $this->loader) {
            return null;
        }

        if ('' === $class) {
            return null;
        }

        if (array_key_exists($class, $this->nodes)) {
            return $this->nodes[$class];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $visitor = new FirstFindingVisitor($filter);

        $traverser->addVisitor($visitor);

        $file = $this->loader->findFile($class);

        $traverser->traverse($this->parser->parse(file_get_contents($file)));

        return $this->nodes[$class] = $visitor->getFoundNode();
    }
}
