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

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use SolidWorx\Burial\PhpParser\NodeLoader;
use SolidWorx\Burial\PhpParser\Visitor\NodePropertiesVisitor;
use SolidWorx\Burial\PhpParser\Visitor\RemoveMethodVisitor;

final class Burial
{
    /** @var \PhpParser\Parser */
    private $parser;

    /** @var Emulative */
    private $lexer;

    /** @var NodeTraverser */
    private $traverser;

    /** @var NodeLoader */
    private $nodeLoader;

    /** @var string */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $autoLoader = null;
        if (is_file($loader = $projectDir.'/vendor/autoload.php')) {
            $autoLoader = require_once $loader;
            $autoLoader->unregister();
        }

        $this->lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);

        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $this->lexer);

        $this->traverser = new NodeTraverser;
        $this->traverser->addVisitor(new CloningVisitor);
        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor(new NodePropertiesVisitor());
        $this->nodeLoader = new NodeLoader($this->parser, $autoLoader);
        $this->projectDir = rtrim($projectDir, '/');
    }

    public function bury(Tomb $tomb): void
    {
        $file = "{$this->projectDir}/{$tomb->file}";

        $oldStmts = $this->parser->parse(file_get_contents($file));

        $oldTokens = $this->lexer->getTokens();

        $newStmts = $this->traverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;

        // @TODO: Handle functions with no scope (E.G closures)
        if ($tomb->scope) {
            $nodeTraverser->addVisitor(new RemoveMethodVisitor($this->nodeLoader, $tomb->scope, $tomb->function));
        }

        file_put_contents($file, (new Standard)->printFormatPreserving($nodeTraverser->traverse($newStmts), $oldStmts, $oldTokens));
    }
}
