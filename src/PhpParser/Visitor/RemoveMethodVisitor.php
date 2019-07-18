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
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SolidWorx\Burial\PhpParser\NodeLoader;

final class RemoveMethodVisitor extends NodeVisitorAbstract
{
    /** @var string */
    private $class;

    /** @var string */
    private $method;

    /** @var NodeLoader */
    private $nodeLoader;

    public function __construct(NodeLoader $nodeLoader, string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
        $this->nodeLoader = $nodeLoader;
    }

    public function leaveNode(Node $node)
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if ($node->getAttribute('className') === $this->class && (string) $node->name === $this->method) {

            if ($node->isAbstract()) {
                return $node;
            }

            $class = $node->getAttribute('classNode');

            if (!$class instanceof Node\Stmt\Class_) {
                return $node;
            }

            // @TODO: Handle traits in class, traits in parent class, parent class extends etc
            if ('' !== $parent = (string) $class->extends) {
                if (class_exists($parent, false)) {
                    $parentClass = new \ReflectionClass($parent);
                } else {
                    $parentClass = $this->nodeLoader->findClassNode($parent);

                    if (null === $parentClass) {
                        throw new \Exception('This class extends another class, and we cant process the parent class to see if this is an abstracted method, so we are just skipping it for now');
                    }
                }

                // Check if method is an abstract method in the parent class, or exists in an interface
                if ($this->isAbstractMethod($parentClass) || $this->checkInterfaces($class->implements)) {
                    return $node;
                }
            }

            return NodeTraverser::REMOVE_NODE;
        }

        return $node;
    }

    private function isAbstractMethod($class): bool
    {
        if ($class instanceof \ReflectionClass) {
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getName() === $this->method) {
                    return $method->isAbstract();
                }
            }
        } else {
            foreach ($class->stmts as $node) {
                if ($node instanceof ClassMethod && (string) $node->name === $this->method) {
                    return $node->isAbstract();
                }
            }
        }

        return false;
    }

    private function interfaceHasMethod($interface): bool
    {
        if ($interface instanceof \ReflectionClass) {
            foreach ($interface->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getName() === $this->method) {
                    return true;
                }
            }
        } else {
            foreach ($interface->stmts as $node) {
                if ($node instanceof ClassMethod && (string) $node->name === $this->method) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkInterfaces(array $interfaces): bool
    {
        foreach ($interfaces as $interface) {
            if (interface_exists((string) $interface, false)) {
                $parent = class_implements((string) $interface, false);
                $interface = new \ReflectionClass((string) $interface);
            } else {
                $interface = $this->nodeLoader->findInterfaceNode((string) $interface);
                $parent = $interface->extends;
            }

            if ($this->interfaceHasMethod($interface) || $this->checkInterfaces($parent)) {
                return true;
            }
        }

        return false;
    }
}
