<?php
// see https://github.com/FriendsOfPHP/PHP-CS-Fixer

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('vendor')
    ->in([__DIR__])
;

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers(['-psr0', 'ordered_use', 'short_array_syntax'])
    ->finder($finder)
;
