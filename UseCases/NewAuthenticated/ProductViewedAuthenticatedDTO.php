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

namespace BaksDev\Products\Viewed\UseCases\NewAuthenticated;

use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductViewedAuthenticatedDTO
{
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private ProductInvariableUid $id;

    #[Assert\Uuid]
    #[Assert\NotBlank]
    private ?UserUid $usr = null;

    /** Дата и время просмотра */
    #[Assert\NotBlank]
    private DateTimeImmutable $viewedDate;

    public function __construct()
    {
        $this->viewedDate = new DateTimeImmutable();
    }

    public function getUsr(): ?UserUid
    {
        return $this->usr;
    }

    public function setUsr(?UserUid $usr): self
    {
        $this->usr = $usr;

        return $this;
    }


    public function getViewedDate(): DateTimeImmutable
    {
        return $this->viewedDate;
    }

    public function getId(): ProductInvariableUid
    {
        return $this->id;
    }

    public function setId(ProductInvariableUid $id): self
    {
        $this->id = $id;

        return $this;
    }

}