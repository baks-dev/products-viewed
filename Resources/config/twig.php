<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Products\Viewed\BaksDevProductsViewedBundle;
use Symfony\Config\TwigConfig;

return static function(TwigConfig $twig) {

    $twig->path(
        BaksDevProductsViewedBundle::PATH.implode(DIRECTORY_SEPARATOR, ['Resources', 'view', '']), // .'Resources/view',
        'products-viewed'
    );

};
