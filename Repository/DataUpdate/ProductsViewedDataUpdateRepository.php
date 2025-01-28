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

namespace BaksDev\Products\Viewed\Repository\DataUpdate;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Users\User\Type\Id\UserUid;
use InvalidArgumentException;

final class ProductsViewedDataUpdateRepository implements ProductsViewedDataUpdateInterface
{
    private UserUid|false $user = false;

    private ProductInvariableUid|false $invariable = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function user(UserUid|string $user): self
    {
        if(is_string($user))
        {
            $this->user = new UserUid($user);
        }

        if($user instanceof UserUid)
        {
            $this->user = $user;
        }

        return $this;
    }

    public function invariable(ProductInvariableUid|string $invariable): self
    {
        if(is_string($invariable))
        {
            $this->invariable = new ProductInvariableUid($invariable);
        }

        if($invariable instanceof ProductInvariableUid)
        {
            $this->invariable = $invariable;
        }

        return $this;
    }


    /**
     * Метод обновляет дату последнего просмотра пользователем продукта
     */
    public function update(): bool
    {
        if($this->invariable === false || $this->user === false)
        {
            throw new InvalidArgumentException('Неверный аргумент invariable либо user');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->update(ProductsViewed::class)
            ->set('viewed_date', 'NOW()');

        $dbal
            ->where('usr = :usr')
            ->setParameter('usr', $this->user, UserUid::TYPE);

        $dbal
            ->andWhere('invariable = :invariable')
            ->setParameter('invariable', $this->invariable, ProductInvariableUid::TYPE);

        return (bool) $dbal->executeStatement() > 0;
    }
}