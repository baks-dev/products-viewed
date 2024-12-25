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

use BaksDev\Products\Viewed\Repository\ProductsViewed\ProductsViewedRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProductsViewedExtension extends AbstractExtension
{

    private string $project_dir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $project_dir,
        private ProductsViewedRepository $productsViewedRepository,
        private readonly Security $security
    )
    {
        $this->project_dir = $project_dir;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_products_viewed',
                [$this, 'renderProductsViewed'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    public function renderProductsViewed(Environment $twig, $currentProductId)
    {
        $currentUser = $this->security->getUser();
        $productsViewed = $currentUser !== null ? $this->productsViewedRepository->findUserProductInvariablesViewed($currentUser->getId()) :
            $this->productsViewedRepository->findAnonymousProductInvariablesViewed();

        /** Подключаем если определен пользовательский шаблон */
        if(file_exists($this->project_dir.'/templates/products-viewed/twig/products.viewed.html.twig'))
        {
            return $twig->render(
                '@Template/products-viewed/twig/products.viewed.html.twig',
                context: [
                    'products_viewed' => $productsViewed,
                    'current_product_id' => $currentProductId,
                ]);
        }

        return $twig->render(
            '@products-viewed/twig/products.viewed.html.twig',
            context: [
                'products_viewed' => $productsViewed,
                'current_product_id' => $currentProductId,
            ]);
    }

}