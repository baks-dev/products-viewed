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

namespace BaksDev\Products\Viewed\Entity;

use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* ProductsViewed */

#[ORM\Entity]
#[ORM\Table(name: 'products_viewed')]
class ProductsViewed
{

    /** Invariable ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductInvariableUid::TYPE)]
    private ProductInvariableUid $invariable;

    /** ID пользователя  */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: UserUid::TYPE)]
    private UserUid $usr;

    /** Дата и время просмотра */
    #[Assert\NotBlank]
    #[ORM\Column(name: 'viewed_date', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $viewedDate;

    public function getViewedDate(): DateTimeImmutable
    {
        return $this->viewedDate;
    }

    public function __construct()
    {
        $this->viewedDate = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        return (string) $this->invariable;
    }

    public function setInvariable(ProductInvariableUid|string $invariable): self
    {
        $this->invariable = $invariable;
        return $this;
    }

    public function setUsr(UserUid|string $usr): self
    {
        $this->usr = $usr;
        return $this;
    }

}