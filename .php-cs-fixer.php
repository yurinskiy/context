<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
;

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ;