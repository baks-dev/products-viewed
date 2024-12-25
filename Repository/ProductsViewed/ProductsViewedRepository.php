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

namespace BaksDev\Products\Viewed\Repository\ProductsViewed;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductsViewedRepository implements ProductsViewedInterface
{
    const int VIEWED_PRODUCTS_LIMIT = 5;

    private SessionInterface|false $session = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly RequestStack $requestStack
    ) {}

    private function builder(): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('product.id AS product_id')
            ->join(
                'invariable',
                Product::class,
                'product',
                'product.id = invariable.product'
            );

        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );


        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->join(
                'product',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product.event AND product_trans.local = :local'
            );


        /**
         * Product url
         */
        $dbal
            ->addSelect('product_info.url AS product_url')
            ->join(
                'product',
                ProductInfo::class,
                'product_info',
                'product_info.event = product.event'
            );

        /**
         * Category url
         */
        $dbal->join(
            'product',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product.event AND product_event_category.root = true'
        );

        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );


        $dbal
            ->leftJoin(
                'product',
                ProductPrice::class,
                'product_price',
                'product_price.event = product.event'
            );

        $dbal
            ->addSelect('product_offer.value AS offer_value') // 16
            ->addSelect('product_offer.postfix AS offer_postfix')
            ->leftJoin(
                'invariable',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product.event AND product_offer.const = invariable.offer'
            );


        $dbal
            ->addSelect('product_variation.value AS variation_value') // 255
            ->addSelect('product_variation.postfix AS variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id AND product_variation.const = invariable.variation'
            );

        $dbal
            ->addSelect('modification.value AS modification_value') // 35
            ->addSelect('modification.postfix AS modification_postfix')
            ->addSelect('modification.article AS modification_article')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'modification',
                'modification.variation = product_variation.id AND modification.const = invariable.modification'
            );

        /**
         * Стоимость продукта
         */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferPrice::class,
                'offer_price',
                'offer_price.offer = product_offer.id'
            );

        $dbal
            ->leftJoin(
                'product_variation',
                ProductVariationPrice::class,
                'variation_price',
                'variation_price.variation = product_variation.id'
            );


        $dbal
            ->leftJoin(
                'modification',
                ProductModificationPrice::class,
                'modification_price',
                'modification_price.modification = modification.id'
            );

        /**
         * Изображение продукта
         */
        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
			   
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
			   
			   ELSE NULL
			END AS product_image_ext
		");


        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
			   
			   ELSE NULL
			END AS product_image_cdn
		");

        /**
         * Цена
         */
        $dbal
            ->addSelect('
            COALESCE(
                modification_price.price,
                variation_price.price,
                offer_price.price,
                product_price.price,
                0
            ) AS price
        ');

        $dbal
            ->addSelect('
            COALESCE(
                modification_price.old,
                variation_price.old,
                offer_price.old,
                product_price.old,
                0
            ) AS old_price
        ');

        $dbal
            ->addSelect('
            COALESCE(
                modification_price.currency,
                variation_price.currency,
                offer_price.currency,
                product_price.currency
            ) AS currency
        ');


        $dbal
            ->setMaxResults(self::VIEWED_PRODUCTS_LIMIT);

        return $dbal;
    }


    /**
     * Получение данных о продукте для анонимного пользователя
     */
    public function findAnonymousProductInvariablesViewed(): array|bool
    {
        if ($this->session === false)
        {
            $this->session = $this->requestStack->getSession();
        }
        $viewedProducts = $this->session->get('viewedProducts') ?? [];

        /**
         * Создание 'CASE' строки для сортировки по $viewedProducts
         */
        $orderByCase = "CASE invariable.id ";

        foreach ($viewedProducts as $key => $viewedProduct)
        {
            $orderByCase .= "WHEN '$viewedProduct' THEN $key ";
        }
        $orderByCase .= " END";

        $dbal = $this->builder();

        $dbal
            ->addSelect('invariable.id')
            ->from(ProductInvariable::class, 'invariable')
            ->where('invariable.id IN (:viewedProducts)')
            ->setParameter('viewedProducts', $viewedProducts, ArrayParameterType::STRING)
            ->addOrderBy($orderByCase)
        ;

        return $dbal->fetchAllAssociative();
    }

    /**
     * Получение данных о продукте для авторизованного пользователя
     */
    public function findUserProductInvariablesViewed(?UserUid $usr): array|bool
    {
        $dbal = $this->builder();

        $dbal
            ->addSelect('viewed.invariable')
            ->from(ProductsViewed::class, 'viewed')
            ->where('viewed.usr = :usr')
            ->setParameter('usr', $usr, UserUid::TYPE);

        $dbal
            ->leftJoin(
                'viewed',
                ProductInvariable::class,
                'invariable',
                'invariable.id = viewed.invariable'
            );

        $dbal->orderBy('viewed.viewed_date', 'DESC');

        return $dbal->fetchAllAssociative();
    }

}