<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Products\Viewed\Repository\ProductsViewed;

use BaksDev\Products\Product\Repository\RepositoryResultInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/** @see ProductsViewedRepository */
#[Exclude]
final readonly class ProductsViewedResult implements RepositoryResultInterface
{
    public function __construct(
        private string|null $invariable_id,

        private string|null $product_id,
        private string|null $product_name,
        private string|null $product_url,

        private string|null $offer_value,
        private string|null $offer_postfix,
        private string|null $offer_reference,

        private string|null $variation_value,
        private string|null $variation_postfix,
        private string|null $variation_reference,

        private string|null $modification_value,
        private string|null $modification_postfix,
        private string|null $modification_reference,

        private string|null $product_article,
        private string|null $product_root_image,

        private int|null $price,
        private int|null $old_price,
        private string|null $currency,
        private int|null $product_quantity,

        private string|null $category_url,

        private int|null $profile_discount = null,
    ) {}

    public function getProductInvariableId(): ?ProductInvariableUid
    {
        if(is_null($this->invariable_id))
        {
            return null;
        }

        return new ProductInvariableUid($this->invariable_id);
    }

    public function getProductId(): ProductUid
    {
        return new ProductUid($this->product_id);
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function getProductUrl(): ?string
    {
        return $this->product_url;
    }

    public function getProductOfferValue(): ?string
    {
        return $this->offer_value;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->offer_postfix;
    }

    public function getProductOfferReference(): ?string
    {
        return $this->offer_reference;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->variation_value;
    }

    public function getProductVariationPostfix(): ?string
    {
        return $this->variation_postfix;
    }

    public function getProductVariationReference(): ?string
    {
        return $this->variation_reference;
    }

    public function getProductModificationValue(): ?string
    {
        return $this->modification_value;
    }

    public function getProductModificationPostfix(): ?string
    {
        return $this->modification_postfix;
    }

    public function getProductArticle(): ?string
    {
        return $this->product_article;
    }

    public function getProductModificationReference(): ?string
    {
        return $this->modification_reference;
    }

    public function getProductRootImage(): ?array
    {
        if(is_null($this->product_root_image))
        {
            return null;
        }

        if(false === json_validate($this->product_root_image))
        {
            return null;
        }

        $images = json_decode($this->product_root_image, true, 512, JSON_THROW_ON_ERROR);

        $rootImage = current($images);

        if(is_null($rootImage))
        {
            return null;
        }

        return $rootImage;
    }

    public function getProductPrice(): Money
    {
        // без применения скидки в профиле пользователя
        if(is_null($this->profile_discount))
        {
            return new Money($this->price, true);
        }

        // применяем скидку пользователя из профиля
        $price = new Money($this->price, true);
        $price->applyPercent($this->profile_discount);

        return $price;
    }

    public function getProductOldPrice(): Money
    {
        // без применения скидки в профиле пользователя
        if(is_null($this->profile_discount))
        {
            return new Money($this->old_price, true);
        }

        // применяем скидку пользователя из профиля
        $price = new Money($this->old_price, true);
        $price->applyPercent($this->profile_discount);

        return $price;
    }

    public function getProductCurrency(): ?string
    {
        return $this->currency;
    }

    public function getProductQuantity(): ?int
    {
        return $this->product_quantity;
    }

    public function getCategoryUrl(): ?string
    {
        return $this->category_url;
    }

    public function getProfileDiscount(): ?int
    {
        return $this->profile_discount;
    }

}
