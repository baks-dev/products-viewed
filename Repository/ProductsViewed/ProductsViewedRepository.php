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
use BaksDev\Products\Promotion\BaksDevProductsPromotionBundle;
use BaksDev\Products\Promotion\Entity\Event\Invariable\ProductPromotionInvariable;
use BaksDev\Products\Promotion\Entity\Event\Period\ProductPromotionPeriod;
use BaksDev\Products\Promotion\Entity\Event\Price\ProductPromotionPrice;
use BaksDev\Products\Promotion\Entity\Event\ProductPromotionEvent;
use BaksDev\Products\Promotion\Entity\ProductPromotion;
use BaksDev\Products\Viewed\Entity\ProductsViewed;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Discount\UserProfileDiscount;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\DBAL\ArrayParameterType;
use Generator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ProductsViewedRepository implements ProductsViewedInterface
{
    const int VIEWED_PRODUCTS_LIMIT = 5;

    private SessionInterface|false $session = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Получение данных о продукте для анонимного пользователя
     *
     * @return Generator<int, ProductsViewedResult>|false
     */
    public function findAnonymousProductInvariablesViewed(): Generator|false
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
            ->addOrderBy($orderByCase)
            ->addGroupBy('invariable.id');

        $dbal->enableCache('products-viewed');

        $result = $dbal->fetchAllHydrate(ProductsViewedResult::class);

        return (true === $result->valid()) ? $result : false;
    }

    /**
     * Получение данных о продукте для авторизованного пользователя
     *
     * @return Generator<int, ProductsViewedResult>|false
     */
    public function findUserProductInvariablesViewed(?UserUid $usr): Generator|false
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
            )
            ->addGroupBy('viewed.viewed_date')
            ->addGroupBy('viewed.invariable');

        $dbal
            ->leftJoin(
                'viewed',
                ProductInvariable::class,
                'invariable',
                'invariable.id = viewed.invariable'
            );

        $dbal->orderBy('viewed.viewed_date', 'DESC');

        $result = $dbal->fetchAllHydrate(ProductsViewedResult::class);

        return (true === $result->valid()) ? $result : false;
    }

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
            ->addSelect('product_modification.value AS modification_value')
            ->addSelect('product_modification.postfix AS modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                '
                    product_modification.variation = product_variation.id AND 
                    product_modification.const = invariable.modification
                ');

        /** Тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );

        /** Артикул продукта */
        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');

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
                'product_modification',
                ProductModificationPrice::class,
                'modification_price',
                'modification_price.modification = product_modification.id'
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
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id'
        );

        $dbal->addSelect("
           CASE
             WHEN product_modification_quantity.quantity IS NOT NULL AND product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve
             THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
             
             WHEN product_variation_quantity.quantity IS NOT NULL AND product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve
             THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
             
             WHEN product_offer_quantity.quantity IS NOT NULL AND product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve
             THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
             
             WHEN product_price.quantity IS NOT NULL AND product_price.quantity > 0 AND product_price.quantity > product_price.reserve
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
        )
            ->addGroupBy('product_photo.ext');

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        )
            ->addGroupBy('product_offer_images.ext');

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        )
            ->addGroupBy('product_variation_image.ext');

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        )
            ->addGroupBy('product_modification_image.ext');

        /** Агрегация фотографий */
        $dbal->addSelect("
            CASE 
            WHEN product_modification_image.ext IS NOT NULL THEN
                JSON_AGG 
                    (DISTINCT
                        JSONB_BUILD_OBJECT
                            (
                                'product_img_root', product_modification_image.root,
                                'product_img', CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name),
                                'product_img_ext', product_modification_image.ext,
                                'product_img_cdn', product_modification_image.cdn
                            )
                    )
            
            WHEN product_variation_image.ext IS NOT NULL THEN
                JSON_AGG
                    (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'product_img_root', product_variation_image.root,
                            'product_img', CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name),
                            'product_img_ext', product_variation_image.ext,
                            'product_img_cdn', product_variation_image.cdn
                        ) 
                    )
                    
            WHEN product_offer_images.ext IS NOT NULL THEN
            JSON_AGG
                (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'product_img_root', product_offer_images.root,
                            'product_img', CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name),
                            'product_img_ext', product_offer_images.ext,
                            'product_img_cdn', product_offer_images.cdn
                        )
                        
                    /*ORDER BY product_photo.root DESC, product_photo.id*/
                )
                
            WHEN product_photo.ext IS NOT NULL THEN
            JSON_AGG
                (DISTINCT
                    JSONB_BUILD_OBJECT
                        (
                            'product_img_root', product_photo.root,
                            'product_img', CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name),
                            'product_img_ext', product_photo.ext,
                            'product_img_cdn', product_photo.cdn
                        )
                    
                    /*ORDER BY product_photo.root DESC, product_photo.id*/
                )
            
            ELSE NULL
            END
			AS product_root_image"
        );

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

        /**
         * ProductInvariable
         */

        $dbal
            ->addSelect('product_invariable.id AS invariable')
            ->leftJoin(
                'product_modification',
                ProductInvariable::class,
                'product_invariable',
                '
                   product_invariable.product = product.id 
                   
                   AND (CASE 
                        WHEN product_offer.const IS NOT NULL 
                        THEN product_invariable.offer = product_offer.const
                        ELSE product_invariable.offer IS NULL
                    END)
                    
                    AND (CASE 
                        WHEN product_variation.const IS NOT NULL 
                        THEN product_invariable.variation = product_variation.const
                        ELSE product_invariable.variation IS NULL
                    END)
                    
                    AND (CASE 
                        WHEN product_modification.const IS NOT NULL 
                        THEN product_invariable.modification = product_modification.const
                        ELSE product_invariable.modification IS NULL
                   END)
           ');

        /**
         * ProductsPromotion
         */

        if(true === class_exists(BaksDevProductsPromotionBundle::class) && true === $dbal->isProjectProfile())
        {
            $dbal
                ->leftJoin(
                    'product_invariable',
                    ProductPromotionInvariable::class,
                    'product_promotion_invariable',
                    '
                        product_promotion_invariable.product = product_invariable.id
                        AND
                        product_promotion_invariable.profile = :'.$dbal::PROJECT_PROFILE_KEY,
                );

            $dbal
                ->leftJoin(
                    'product_promotion_invariable',
                    ProductPromotion::class,
                    'product_promotion',
                    'product_promotion.id = product_promotion_invariable.main',
                );

            $dbal
                ->leftJoin(
                    'product_promotion',
                    ProductPromotionEvent::class,
                    'product_promotion_event',
                    '
                        product_promotion_event.main = product_promotion.id',
                );

            $dbal
                ->addSelect('product_promotion_price.value AS promotion_price')
                ->leftJoin(
                    'product_promotion_event',
                    ProductPromotionPrice::class,
                    'product_promotion_price',
                    'product_promotion_price.event = product_promotion.event',
                );

            $dbal
                ->addSelect('
                CASE
                    WHEN 
                        CURRENT_DATE >= product_promotion_period.date_start
                        AND
                         (
                            product_promotion_period.date_end IS NULL OR CURRENT_DATE <= product_promotion_period.date_end
                         )
                    THEN true
                    ELSE false
                END AS promotion_active
            ')
                ->leftJoin(
                    'product_promotion_event',
                    ProductPromotionPeriod::class,
                    'product_promotion_period',
                    '
                        product_promotion_period.event = product_promotion.event',
                );
        }

        /** Персональная скидка из профиля авторизованного пользователя */
        if(true === $dbal->bindCurrentProfile())
        {

            $dbal
                ->join(
                    'product',
                    UserProfile::class,
                    'current_profile',
                    '
                        current_profile.id = :'.$dbal::CURRENT_PROFILE_KEY
                );

            $dbal
                ->addSelect('current_profile_discount.value AS profile_discount')
                ->leftJoin(
                    'current_profile',
                    UserProfileDiscount::class,
                    'current_profile_discount',
                    '
                        current_profile_discount.event = current_profile.event
                        '
                );
        }

        /** Общая скидка (наценка) из профиля магазина */
        if(true === $dbal->bindProjectProfile())
        {

            $dbal
                ->join(
                    'product',
                    UserProfile::class,
                    'project_profile',
                    '
                        project_profile.id = :'.$dbal::PROJECT_PROFILE_KEY
                );

            $dbal
                ->addSelect('project_profile_discount.value AS project_discount')
                ->leftJoin(
                    'project_profile',
                    UserProfileDiscount::class,
                    'project_profile_discount',
                    '
                        project_profile_discount.event = project_profile.event'
                );
        }

        $dbal->allGroupByExclude();
        $dbal->setMaxResults(self::VIEWED_PRODUCTS_LIMIT);

        return $dbal;
    }
}