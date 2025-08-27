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
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\Tests\ProductViewedAuthenticatedNewTest;
use BaksDev\Users\User\Type\Id\UserUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group products-viewed
 * @group products-viewed-repo
 *
 * @depends BaksDev\Products\Viewed\UseCases\NewAuthenticated\Tests\ProductViewedAuthenticatedNewTest::class
 */
#[Group('products-viewed')]
#[When(env: 'test')]
class ProductsViewedRepositoryTest extends KernelTestCase
{
    #[DependsOnClass(ProductViewedAuthenticatedNewTest::class)]
    public function testFindUserProductInvariablesViewed()
    {
        /** @var ProductsViewedRepository $ProductsViewedRepository */
        $ProductsViewedRepository = self::getContainer()->get(ProductsViewedRepository::class);

        $viewedProducts = $ProductsViewedRepository->findUserProductInvariablesViewed(new UserUid(UserUid::TEST));

        self::assertNotFalse($viewedProducts, 'Просмотренные продукты не добавлены в базу данных');
    }

}