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

namespace BaksDev\Products\Viewed\UseCases\NewAuthenticated\Tests;

use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewAdminUseCaseTest;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ProductViewedAuthenticatedDTO;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ProductViewedAuthenticatedHandler;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('products-viewed')]
class ProductViewedAuthenticatedNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** Создаем тестовый продукт */
        ProductsProductNewAdminUseCaseTest::setUpBeforeClass();
        new ProductsProductNewAdminUseCaseTest()->testUseCase();

        self::ensureKernelShutdown();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(ProductsViewed::class)
            ->findOneBy([
                'usr' => UserUid::TEST,
                'invariable' => ProductInvariableUid::TEST
            ]);

        if($main)
        {
            $em->remove($main);
            $em->flush();

        }

        $em->clear();
    }


    public function testUseCase(): void
    {

        /** @see ProductViewedAuthenticatedDTO */
        $ViewedAuthenticatedDTO = new ProductViewedAuthenticatedDTO();

        $ViewedAuthenticatedDTO->setId($ProductInvariableUid = new ProductInvariableUid(ProductInvariableUid::TEST));
        self::assertSame($ProductInvariableUid, $ViewedAuthenticatedDTO->getId());


        $ViewedAuthenticatedDTO->setUsr($UserUid = new UserUid(UserUid::TEST));
        self::assertSame($UserUid, $ViewedAuthenticatedDTO->getUsr());


        /** @var ProductViewedAuthenticatedHandler $ProductViewedAuthenticated */
        $ProductViewedAuthenticated = self::getContainer()->get(ProductViewedAuthenticatedHandler::class);
        $handle = $ProductViewedAuthenticated->addViewedProduct($ViewedAuthenticatedDTO);

        if($handle === true)
        {
            self::assertTrue($handle);
            return;
        }

        self::assertTrue(($handle instanceof ProductsViewed), $handle.': Ошибка ProductViewed');
    }
}