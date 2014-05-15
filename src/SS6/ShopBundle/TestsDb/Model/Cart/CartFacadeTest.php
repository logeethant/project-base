<?php

namespace SS6\ShopBundle\TestsDb\Model\Cart;

use SS6\ShopBundle\Model\Cart\Cart;
use SS6\ShopBundle\Model\Cart\CartFacade;
use SS6\ShopBundle\Model\Cart\CartItem;
use SS6\ShopBundle\Model\Cart\CartSingletonFactory;
use SS6\ShopBundle\Model\Customer\CustomerIdentifier;
use SS6\ShopBundle\Model\Product\Product;
use SS6\ShopBundle\Component\Test\DatabaseTestCase;

class CartFacadeTest extends DatabaseTestCase {
	public function testAddProductToCart() {
		$em = $this->getEntityManager();
		$cartService = $this->getContainer()->get('ss6.shop.cart.cart_service');
		$productRepository = $this->getContainer()->get('ss6.shop.product.product_repository');
		$customerIdentifier = new CustomerIdentifier('secreetSessionHash');
		$cartItemRepository = $this->getContainer()->get('ss6.shop.cart.cart_item_repository');

		$product = new Product('productName');
		$em->persist($product);
		$em->flush();
		$productId = $product->getId();
		$em->clear();
		$quantity = 10;
		$productVisibilityRepository = $this->getContainer()->get('ss6.shop.product.product_visibility_repository');
		/* @var $productVisibilityRepository \SS6\ShopBundle\Model\Product\ProductVisibilityRepository */
		$productVisibilityRepository->refreshProductsVisibility();

		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$cartFacade = new CartFacade($this->getEntityManager(), $cartService, $cart, $productRepository, $customerIdentifier);
		$cartFacade->addProductToCart($productId, $quantity);

		$em->clear();
		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$this->assertEquals($quantity, $cart->getQuantity(), 'Add correct quantity product');
		$cartItems = $cart->getItems();
		$product = array_pop($cartItems)->getProduct();
		$this->assertEquals($productId, $product->getId(), 'Add correct product');

		$customerIdentifier = new CustomerIdentifier('anotherSecreetSessionHash');
		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$this->assertEquals(0, $cart->getItemsCount(), 'Add only in their own basket');
	}

	public function testChangeQuantities() {
		$em = $this->getEntityManager();
		$cartService = $this->getContainer()->get('ss6.shop.cart.cart_service');
		$productRepository = $this->getContainer()->get('ss6.shop.product.product_repository');
		$customerIdentifier = new CustomerIdentifier('secreetSessionHash');
		$cartItemRepository = $this->getContainer()->get('ss6.shop.cart.cart_item_repository');

		$product1 = new Product('productName');
		$product2 = new Product('otherProductName');
		$em->persist($product1);
		$em->persist($product2);
		$em->flush();
		$em->clear();
		$productVisibilityRepository = $this->getContainer()->get('ss6.shop.product.product_visibility_repository');
		/* @var $productVisibilityRepository \SS6\ShopBundle\Model\Product\ProductVisibilityRepository */
		$productVisibilityRepository->refreshProductsVisibility();

		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$cartFacade = new CartFacade($this->getEntityManager(), $cartService, $cart, $productRepository, $customerIdentifier);
		$cartItem1 = $cartFacade->addProductToCart($product1->getId(), 1)->getCartItem();
		$cartItem2 = $cartFacade->addProductToCart($product2->getId(), 2)->getCartItem();
		$em->flush();
		$em->clear();

		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$cartFacade = new CartFacade($this->getEntityManager(), $cartService, $cart, $productRepository, $customerIdentifier);
		$cartFacade->changeQuantities(array(
			$cartItem1->getId() => 5,
			$cartItem2->getId() => 9,
		));
		$em->flush();

		$em->clear();
		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		foreach ($cart->getItems() as $cartItem) {
			if ($cartItem->getId() === $cartItem1->getId()) {
				$this->assertEquals(5, $cartItem->getQuantity(), 'Correct change quantity product');
			} elseif ($cartItem->getId() === $cartItem2->getId()) {
				$this->assertEquals(9, $cartItem->getQuantity(), 'Correct change quantity product');
			} else {
				$this->fail('Unexpected product in cart');
			}
		}
	}

	public function testDeleteCartItemNonexistItem() {
		$em = $this->getEntityManager();

		$cartService = $this->getContainer()->get('ss6.shop.cart.cart_service');
		$productRepository = $this->getContainer()->get('ss6.shop.product.product_repository');
		$customerIdentifier = new CustomerIdentifier('randomString');

		$product = new Product('productName');
		$em->persist($product);
		$cartItem = new CartItem($customerIdentifier, $product, 1);
		$em->persist($cartItem);
		$cartItems = array($cartItem);
		$cart = new Cart($cartItems);
		$em->flush();

		$cartFacade = new CartFacade($this->getEntityManager(), $cartService, $cart, $productRepository, $customerIdentifier);
		$this->setExpectedException('\SS6\ShopBundle\Model\Cart\Exception\InvalidCartItemException');
		$cartFacade->deleteCartItem($cartItem->getId() + 1);
	}

	public function testDeleteCartItem() {
		$em = $this->getEntityManager();

		$cartService = $this->getContainer()->get('ss6.shop.cart.cart_service');
		$productRepository = $this->getContainer()->get('ss6.shop.product.product_repository');
		$customerIdentifier = new CustomerIdentifier('randomString');
		$cartItemRepository = $this->getContainer()->get('ss6.shop.cart.cart_item_repository');

		$product1 = new Product('productName1');
		$product2 = new Product('productName2');
		$em->persist($product1);
		$em->persist($product2);
		$cartItem1 = new CartItem($customerIdentifier, $product1, 1);
		$cartItem2 = new CartItem($customerIdentifier, $product2, 1);
		$em->persist($cartItem1);
		$em->persist($cartItem2);
		$cartItems = array($cartItem1, $cartItem2);
		$cart = new Cart($cartItems);
		$em->flush();

		$cartFacade = new CartFacade($this->getEntityManager(), $cartService, $cart, $productRepository, $customerIdentifier);
		$cartFacade->deleteCartItem($cartItem1->getId());

		$em->clear();
		
		$cartSingletonFactory = new CartSingletonFactory($cartItemRepository);
		$cart = $cartSingletonFactory->get($customerIdentifier);
		$this->assertEquals(1, $cart->getItemsCount());
		$cartItems = $cart->getItems();
		$cartItem = array_pop($cartItems);
		$this->assertEquals($cartItem2->getId(), $cartItem->getId());
	}
	
}