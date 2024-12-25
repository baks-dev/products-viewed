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

namespace BaksDev\Products\Viewed\UseCases\NewAnonymous\Tests;

use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group products-viewed
 */
#[When(env: 'test')]
class ProductViewedAnonymousNewTest extends WebTestCase
{
    private const string URL = '/catalog/new/new_info_url/100/200/300';

    public function testNewAnonymous()
    {

        $client = static::createClient();

        /**
         * Cоздать тестовый продукт, с минимум данных
         */
        $client->request('GET', self::URL);

        $session = $client->getRequest()->getSession();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        /**
         * Проверить что ключ существует в сессии
         */
        self::assertNotNull($session->get('viewedProducts'));

        foreach($session->get('viewedProducts') as $product)
        {
            /**
             * Проверить что такая запись существует в таблице products_invariable
             *
             * @var ProductInvariableUid $product
             */
            $productInvariable = $em
                ->getRepository(ProductInvariable::class)
                ->findOneBy(['id' => $product]);

            self::assertTrue($productInvariable instanceof ProductInvariable);
        }

        /**
         * Проверить кол-во
         */
        self::assertCount(1, $session->get('viewedProducts'));
    }
}