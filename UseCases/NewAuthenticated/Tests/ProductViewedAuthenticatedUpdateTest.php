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
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewTest;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ProductViewedAuthenticated;
use BaksDev\Products\Viewed\UseCases\NewAuthenticated\ViewedAuthenticatedDTO;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group products-viewed
 */
#[When(env: 'test')]
class ProductViewedAuthenticatedUpdateTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        ProductsProductNewTest::setUpBeforeClass();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(ProductsViewed::class)
            ->findOneBy(['usr' => UserUid::TEST]);

        if($main)
        {
            $em->remove($main);
        }

        $em->flush();
        $em->clear();
    }

    public function testUseCaseUpdate(): void
    {
        /**
         * Создать тестовую запись
         */
        $ProductsProductNewTest = new ProductsProductNewTest();
        $ProductsProductNewTest->testUseCase();

        /** @see ViewedAuthenticatedDTO */
        $ViewedAuthenticatedDTO = new ViewedAuthenticatedDTO();

        $productIvariableUid = new ProductInvariableUid();
        $ViewedAuthenticatedDTO
            ->setId($productIvariableUid)
            ->setUsr(new UserUid());

        /** @var ProductViewedAuthenticated $ProductViewedAuthenticated */
        $ProductViewedAuthenticated = self::getContainer()->get(ProductViewedAuthenticated::class);
        $ProductViewedAuthenticated->addViewedProduct($ViewedAuthenticatedDTO);

        /**
         * Получить данные по времени создания
         */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $created = $em
            ->getRepository(ProductsViewed::class)
            ->findOneBy(['usr' => UserUid::TEST, 'invariable' => $productIvariableUid]);
        $em->clear();

        /**
         * Обновить запись
         */
        $ViewedAuthenticatedDTO = new ViewedAuthenticatedDTO();
        $ViewedAuthenticatedDTO
            ->setId($productIvariableUid)
            ->setUsr(new UserUid());

        /** @var ProductViewedAuthenticated $ProductViewedAuthenticated */
        $ProductViewedAuthenticated = self::getContainer()->get(ProductViewedAuthenticated::class);
        $result = $ProductViewedAuthenticated->addViewedProduct($ViewedAuthenticatedDTO);

        /**
         * Проверить, что запись обновилась
         */
        self::assertTrue($result);

        /**
         * Проверить дату обновления
         */
        $updated = $em
            ->getRepository(ProductsViewed::class)
            ->findOneBy(['usr' => UserUid::TEST, 'invariable' => $productIvariableUid]);

        /**
         * Проверить, что даты создания и обновления отличаются
         */
        self::assertNotEquals($created->getViewedDate(), $updated->getViewedDate());
    }
}