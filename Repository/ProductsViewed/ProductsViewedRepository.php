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
 *
 */

declare(strict_types=1);

namespace BaksDev\Products\Viewed\Repository\ProductsViewed;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
            '
                product_event_category.event = product.event AND 
                product_event_category.root = true
            ');

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


        /** OFFER */
        $dbal
            ->addSelect('product_offer.value AS offer_value')
            ->addSelect('product_offer.postfix AS offer_postfix')
            ->leftJoin(
                'invariable',
                ProductOffer::class,
                'product_offer',
                '
                    product_offer.event = product.event AND 
                    product_offer.const = invariable.offer
                ');

        /**  Тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /** VARIATION */
        $dbal
            ->addSelect('product_variation.value AS variation_value')
            ->addSelect('product_variation.postfix AS variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                '
                    product_variation.offer = product_offer.id AND 
                    product_variation.const = invariable.variation
                ');

        /** Тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference as variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );

        /** MODIFICATION */
        $dbal
            ->addSelect('modification.value AS modification_value')
            ->addSelect('modification.postfix AS modification_postfix')
            ->addSelect('modification.article AS modification_article')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'modification',
                '
                    modification.variation = product_variation.id AND 
                    modification.const = invariable.modification
                ');

        /** Тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as modification_reference')
            ->leftJoin(
                'modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = modification.category_modification'
            );

        /**
         * Стоимость продукта
         */
        $dbal->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event'
        );

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
         * Наличие
         */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );

        $dbal->leftJoin(
            'modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = modification.id'
        );

        $dbal->addSelect("
           CASE
             WHEN product_modification_quantity.quantity IS NOT NULL 
             THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
             
             WHEN product_variation_quantity.quantity IS NOT NULL 
             THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
             
             WHEN product_offer_quantity.quantity IS NOT NULL 
             THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
             
             WHEN product_price.quantity  IS NOT NULL 
             THEN (product_price.quantity - product_price.reserve)
             
             ELSE 0
           END AS product_quantity
          "
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
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'modification',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = modification.id AND product_modification_image.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)
			
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
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.ext
			
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
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.cdn
			
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
			   
			   ELSE NULL
			END AS product_image_cdn
		");

        /** Цена */
        $dbal->addSelect('
			COALESCE(
                NULLIF(modification_price.price, 0), 
                NULLIF(variation_price.price, 0), 
                NULLIF(offer_price.price, 0), 
                NULLIF(product_price.price, 0),
                0
            ) AS price
		');

        /** Старая цена */
        $dbal->addSelect('
			COALESCE(
                NULLIF(modification_price.old, 0), 
                NULLIF(variation_price.old, 0), 
                NULLIF(offer_price.old, 0), 
                NULLIF(product_price.old, 0),
                0
            ) AS old_price
		');

        /** Валюта */
        $dbal
            ->addSelect('
            COALESCE(
                modification_price.currency,
                variation_price.currency,
                offer_price.currency,
                product_price.currency
            ) AS currency
        ');


        $dbal->setMaxResults(self::VIEWED_PRODUCTS_LIMIT);

        return $dbal;
    }


    /**
     * Получение данных о продукте для анонимного пользователя
     */
    public function findAnonymousProductInvariablesViewed(): array|false
    {
        if($this->session === false)
        {
            $this->session = $this->requestStack->getSession();
        }

        $viewedProducts = $this->session->get('viewedProducts') ?? [];

        if(empty($viewedProducts))
        {
            return false;
        }

        /**
         * Создание 'CASE' строки для сортировки по $viewedProducts
         */
        $orderByCase = " CASE invariable.id ";

        $productsCount = 0;

        foreach($viewedProducts as $viewedProduct)
        {
            $orderByCase .= " WHEN '$viewedProduct' THEN $productsCount ";
            $productsCount++;
        }

        $orderByCase .= " END ";

        /**
         * Применяем сортировку $viewedProducts к результату запроса
         */

        $dbal = $this->builder();

        $dbal
            ->addSelect('invariable.id as invariable_id')
            ->from(ProductInvariable::class, 'invariable')
            ->where('invariable.id IN (:viewedProducts)')
            ->setParameter(
                key: 'viewedProducts',
                value: $viewedProducts,
                type: ArrayParameterType::STRING
            )
            ->addOrderBy($orderByCase);

        return $dbal
            ->enableCache('products-viewed')
            ->fetchAllAssociative() ?: false;
    }

    /**
     * Получение данных о продукте для авторизованного пользователя
     */
    public function findUserProductInvariablesViewed(?UserUid $usr): array|false
    {
        $dbal = $this->builder();

        $dbal
            ->addSelect('viewed.invariable as invariable_id')
            ->from(ProductsViewed::class, 'viewed')
            ->where('viewed.usr = :usr')
            ->setParameter(
                key: 'usr',
                value: $usr,
                type: UserUid::TYPE
            );

        $dbal
            ->leftJoin(
                'viewed',
                ProductInvariable::class,
                'invariable',
                'invariable.id = viewed.invariable'
            );

        $dbal->orderBy('viewed.viewed_date', 'DESC');

        return $dbal->fetchAllAssociative() ?: false;
    }

}