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
namespace BaksDev\Products\Viewed\UseCases\NewAuthenticated;

use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Products\Viewed\Repository\DataUpdate\DataUpdateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class ProductViewedAuthenticated
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private DataUpdateInterface $dataUpdate,
        private LoggerInterface $logger,
    ) {}

    public function addViewedProduct(ViewedAuthenticatedDTO $dto): ProductsViewed|bool
    {
        $isUpdate = $this->dataUpdate
            ->user($dto->getUsr())
            ->invariable($dto->getId())
            ->update();

        if(true === $isUpdate)
        {
            return true;
        }

        $ProductsViewed = new ProductsViewed();
        $ProductsViewed
            ->setUsr($dto->getUsr())
            ->setInvariable($dto->getId());

        $errors = $this->validator->validate($ProductsViewed);

        if(count($errors) > 0)
        {
            $this->logger->critical((string) $errors);
            return false;
        }

        $this->entityManager->persist($ProductsViewed);
        $this->entityManager->flush();

        return $ProductsViewed;

    }
}