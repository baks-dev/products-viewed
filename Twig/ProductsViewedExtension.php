<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Products\Viewed\Twig;

use BaksDev\Core\Twig\TemplateExtension;
use BaksDev\Products\Viewed\Repository\ProductsViewed\ProductsViewedRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ProductsViewedExtension extends AbstractExtension
{
    public function __construct(
        private readonly TemplateExtension $template,
        private readonly ProductsViewedRepository $productsViewedRepository,
        private readonly Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_products_viewed',
                [$this, 'renderProductsViewed'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    public function renderProductsViewed(Environment $twig, mixed $currentInvariableId = null): string
    {
        $currentUser = $this->security->getUser();

        if($currentUser !== null)
        {
            $productsViewed = $this->productsViewedRepository->findUserProductInvariablesViewed($currentUser->getId());
        }
        else
        {
            $productsViewed = $this->productsViewedRepository->findAnonymousProductInvariablesViewed();
        }

        if(false === $productsViewed)
        {
            return '';
        }

        $path = $this->template->extends('@products-viewed:render_products_viewed/template.html.twig');

        return $twig->render
        (
            name: $path,
            context: [
                'products_viewed' => iterator_to_array($productsViewed),
                'current_invariable_id' => $currentInvariableId,
            ]
        );
    }
}