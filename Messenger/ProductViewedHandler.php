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

declare(strict_types=1);

namespace BaksDev\Products\Viewed\Messenger;

use BaksDev\Products\Product\Repository\ProductInvariable\ProductInvariableRepository;
use BaksDev\Products\Viewed\UseCases\NewAnonymous\ViewedAnonymousDTO;
use BaksDev\Products\Viewed\UseCases\NewAnonymous\ProductViewedAnonymous;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ViewedAuthenticatedDTO;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ProductViewedAuthenticated;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class ProductViewedHandler
{

    public function __construct(
        private ProductViewedAnonymous  $viewedAnonymous,
        private ProductViewedAuthenticated $viewedAuthenticated,
        private ProductInvariableRepository $productInvariableRepository,
    ) {}

    public function __invoke(ProductViewedMessage $message): void
    {

        $invariable = $this->productInvariableRepository->product($message->getId())
            ->offer($message->getProductOfferConst())
            ->variation($message->getProductVariationConst())
            ->modification($message->getProductModificationConst())
            ->find();

        if ($invariable === false) {
            return;
        }

        /**
         * Для анонимных данные помещаются в сессию
         */
        if ($message->getUsr() === false)
        {
            $anonymousDto = new ViewedAnonymousDTO();
            $anonymousDto->setId($invariable);

            $this->viewedAnonymous->addViewedProduct($anonymousDto);

            return;
        }

        /**
         * Для авторизованных данные в БД в products_viewed
         */
        $authenticatedDto = new ViewedAuthenticatedDTO();
        $authenticatedDto
            ->setId($invariable)
            ->setUsr(new UserUid($message->getUsr()));

        $this->viewedAuthenticated->addViewedProduct($authenticatedDto);

    }
}
