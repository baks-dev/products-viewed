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

namespace BaksDev\Products\Viewed\Messenger;

use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\User\Type\Id\UserUid;

final class ProductViewedMessage
{

    private string $id;

    private ?string $product_offer_const;

    private ?string $product_variation_const;

    private ?string $product_modification_const;

    private ?string $usr;

    public function __construct(
        ProductUid|string $id,
        ProductOfferConst|string|null $product_offer_const,
        ProductVariationConst|string|null $product_variation_const,
        ProductModificationConst|string|null $product_modification_const,
        UserUid|string|null $usr,
    )
    {
        $this->id = (string) $id;
        $this->product_offer_const = $product_offer_const ? (string) $product_offer_const : null;
        $this->product_variation_const = $product_variation_const ? (string) $product_variation_const : null;
        $this->product_modification_const = $product_modification_const ? (string) $product_modification_const : null;
        $this->usr = $usr ? (string) $usr : null;
    }

    /**
     * Идентификатор
     */
    public function getId(): ProductUid
    {
        return new ProductUid($this->id);
    }

    public function getProductOfferConst(): ProductOfferConst|false
    {
        return $this->product_offer_const ? new ProductOfferConst($this->product_offer_const) : false;
    }

    public function getProductVariationConst(): ProductVariationConst|false
    {
        return $this->product_variation_const ? new ProductVariationConst($this->product_variation_const) : false;
    }

    public function getProductModificationConst(): ProductModificationConst|false
    {
        return $this->product_modification_const ? new ProductModificationConst($this->product_modification_const) : false;
    }

    public function getUsr(): UserUid|false
    {
        return $this->usr ? new UserUid($this->usr) : false;
    }

}
