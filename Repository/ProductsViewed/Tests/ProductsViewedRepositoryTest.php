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

namespace BaksDev\Products\Viewed\Repository\ProductsViewed\Tests;

use BaksDev\Products\Viewed\Repository\ProductsViewed\ProductsViewedRepository;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\Tests\ProductViewedAuthenticatedTest;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group products-viewed
 *
 */
#[When(env: 'test')]
class ProductsViewedRepositoryTest extends KernelTestCase
{
    public function testFindUserProductInvariablesViewed()
    {
        /** @var ProductsViewedRepository $ProductsViewedRepository */
        $ProductsViewedRepository = self::getContainer()->get(ProductsViewedRepository::class);

        $viewedProducts = $ProductsViewedRepository->findUserProductInvariablesViewed(new UserUid(UserUid::TEST));

        if($viewedProducts === false)
        {
            self::assertFalse(false);
            return;
        }

        $current = current($viewedProducts);

        if($current === false)
        {
            self::assertFalse(false);
            return;
        }

        $array_keys = [
            "invariable_id",
            "product_name",

            "offer_value",
            "offer_postfix",
            "offer_reference",

            "variation_value",
            "variation_postfix",
            "variation_reference",

            "modification_value",
            "modification_postfix",
            "modification_article",
            "modification_reference",

            "product_quantity",
            "product_id",
            "product_image_cdn",
            "product_image_ext",
            "product_image",

            "price",
            "currency",
            "old_price",
            "product_url",
            "category_url",
        ];

        foreach($current as $key => $value)
        {
            self::assertTrue(in_array($key, $array_keys), sprintf('Появился новый ключ %s', $key));
        }

        self::assertTrue(array_key_exists('invariable_id', $current));
        self::assertTrue(array_key_exists('product_name', $current));

        self::assertTrue(array_key_exists('offer_value', $current));
        self::assertTrue(array_key_exists('offer_postfix', $current));

        self::assertTrue(array_key_exists('variation_value', $current));
        self::assertTrue(array_key_exists('variation_postfix', $current));

        self::assertTrue(array_key_exists('modification_value', $current));
        self::assertTrue(array_key_exists('modification_postfix', $current));
        self::assertTrue(array_key_exists('modification_article', $current));

        self::assertTrue(array_key_exists('product_quantity', $current));
        self::assertTrue(array_key_exists('product_id', $current));
        self::assertTrue(array_key_exists('product_image', $current));
        self::assertTrue(array_key_exists('product_image_cdn', $current));
        self::assertTrue(array_key_exists('product_image_ext', $current));

        self::assertTrue(array_key_exists('price', $current));
        self::assertTrue(array_key_exists('currency', $current));
        self::assertTrue(array_key_exists('old_price', $current));

        self::assertTrue(array_key_exists('product_url', $current));
        self::assertTrue(array_key_exists('category_url', $current));

        /**
         * Проверить, что кол-во результатов не больше ProductsViewedRepository::VIEWED_PRODUCTS_LIMIT
         */
        self::assertTrue(count($viewedProducts) <= ProductsViewedRepository::VIEWED_PRODUCTS_LIMIT);
    }

}