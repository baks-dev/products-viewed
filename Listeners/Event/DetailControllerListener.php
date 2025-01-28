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

namespace BaksDev\Products\Viewed\Listeners\Event;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Product\Controller\User\DetailController;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByValueInterface;
use BaksDev\Products\Viewed\Messenger\ProductViewedMessage;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

#[AsEventListener(event: ControllerArgumentsEvent::class)]
final readonly class DetailControllerListener
{
    public function __construct(
        private ProductDetailByValueInterface $productDetail,
        private MessageDispatchInterface $messageDispatch,
        private Security $security
    ) {}

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if(is_array($event->getController()))
        {
            $Controller = current($event->getController());

            if($Controller instanceof DetailController)
            {
                $args = $event->getNamedArguments();

                $info = $args['info'] ?? null;
                $product = $info?->getProduct();

                if($product)
                {
                    $offer = $args['offer'] ?? null;
                    $variation = $args['variation'] ?? null;
                    $modification = $args['modification'] ?? null;
                    $postfix = $args['postfix'] ?? null;

                    $card = $this->productDetail
                        ->fetchProductAssociative(
                            $product,
                            $offer,
                            $variation,
                            $modification,
                            $postfix
                        );

                    if($card)
                    {
                        $currentUser = $this->security->getUser();

                        $ProductViewedMessage = new ProductViewedMessage(
                            $card['id'],
                            $card['product_offer_const'],
                            $card['product_variation_const'],
                            $card['product_modification_const'],
                            $currentUser !== null ? new UserUid($currentUser->getId()) : null,
                        );

                        $this->messageDispatch->dispatch(
                            $ProductViewedMessage,
                            transport: $currentUser ? 'products-viewed' : null
                        );
                    }
                }
            }
        }
    }
}