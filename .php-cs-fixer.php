<?php

$finder = PhpCsFixer\Finder::create()
    ->in(["config", "database", "src", "tests"]);

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;