<?php

namespace Shopsys\ShopBundle\Component\Javascript\Compiler;

use PLUG\JavaScript\JNodes\nonterminal\JProgramNode;

interface JsCompilerPassInterface
{
    public function process(JProgramNode $node);
}