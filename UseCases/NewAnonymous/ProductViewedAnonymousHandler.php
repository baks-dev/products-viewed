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

namespace BaksDev\Products\Viewed\UseCases\NewAnonymous;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ProductViewedAnonymousHandler
{
    public function __construct(private RequestStack $requestStack) {}

    public function addViewedProduct(ProductViewedAnonymousDTO $dto): void
    {
        try
        {
            $session = $this->requestStack->getSession();
        }
        catch(SessionNotFoundException)
        {
            return;
        }

        $viewedProducts = $session->get('viewedProducts') ?? [];

        /**
         * Для уникальности значений добавляем в начало массива с ключом,
         * имеющим значение самого элемента
         */
        $viewedProducts = [(string)$dto->getId() => (string)$dto->getId()] + $viewedProducts;

        /**
         * Обновить данные в сессии
         */
        $session->set('viewedProducts', $viewedProducts);
    }
}