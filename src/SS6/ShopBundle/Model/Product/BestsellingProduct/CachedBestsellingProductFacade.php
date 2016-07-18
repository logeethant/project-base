<?php

namespace SS6\ShopBundle\Model\Product\BestsellingProduct;

use Doctrine\Common\Cache\CacheProvider;
use SS6\ShopBundle\Model\Category\Category;
use SS6\ShopBundle\Model\Pricing\Group\PricingGroup;
use SS6\ShopBundle\Model\Pricing\Group\PricingGroupRepository;
use SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductFacade;
use SS6\ShopBundle\Model\Product\Detail\ProductDetailFactory;
use SS6\ShopBundle\Model\Product\ProductRepository;
use SS6\ShopBundle\Model\Product\ProductService;

class CachedBestsellingProductFacade {

	const LIFETIME = 43200; // 12h

	/**
	 * @var \SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductFacade
	 */
	private $bestsellingProductFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Detail\ProductDetailFactory
	 */
	private $productDetailFactory;

	/**
	 * @var \Doctrine\Common\Cache\CacheProvider
	 */
	private $cacheProvider;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductRepository
	 */
	private $productRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductService
	 */
	private $productService;

	/**
	 * @var \SS6\ShopBundle\Model\Pricing\Group\PricingGroupRepository
	 */
	private $pricingGroupRepository;

	public function __construct(
		CacheProvider $cacheProvider,
		BestsellingProductFacade $bestsellingProductFacade,
		ProductDetailFactory $productDetailFactory,
		ProductRepository $productRepository,
		ProductService $productService,
		PricingGroupRepository $pricingGroupRepository
	) {
		$this->cacheProvider = $cacheProvider;
		$this->bestsellingProductFacade = $bestsellingProductFacade;
		$this->productDetailFactory = $productDetailFactory;
		$this->productRepository = $productRepository;
		$this->productService = $productService;
		$this->pricingGroupRepository = $pricingGroupRepository;
	}

	/**
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return \SS6\ShopBundle\Model\Product\Detail\ProductDetail[]
	 */
	public function getAllOfferedProductDetails($domainId, Category $category, PricingGroup $pricingGroup) {
		$cacheId = $this->getCacheId($domainId, $category, $pricingGroup);
		$sortedProducts = $this->cacheProvider->fetch($cacheId);

		if ($sortedProducts === false) {
			$bestsellingProductDetails = $this->bestsellingProductFacade->getAllOfferedProductDetails(
				$domainId,
				$category,
				$pricingGroup
			);
			$this->saveToCache($bestsellingProductDetails, $cacheId);

			return $bestsellingProductDetails;
		} else {
			return $this->getSortedProductDetails($domainId, $pricingGroup, $sortedProducts);
		}
	}

	/**
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 */
	public function invalidateCacheByDomainIdAndCategory($domainId, Category $category) {
		$pricingGroups = $this->pricingGroupRepository->getPricingGroupsByDomainId($domainId);
		foreach ($pricingGroups as $pricingGroup) {
			$cacheId = $this->getCacheId($domainId, $category, $pricingGroup);
			$this->cacheProvider->delete($cacheId);
		}
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Detail\ProductDetail[] $bestsellingProductDetails
	 * @param string $cacheId
	 */
	private function saveToCache(array $bestsellingProductDetails, $cacheId) {
		$sortedProductIds = [];
		foreach ($bestsellingProductDetails as $productDetail) {
			$sortedProductIds[] = $productDetail->getProduct()->getId();
		}

		$this->cacheProvider->save($cacheId, $sortedProductIds, self::LIFETIME);
	}

	/**
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @param int[] $sortedProductIds
	 * @return \SS6\ShopBundle\Model\Product\Detail\ProductDetail[]
	 */
	private function getSortedProductDetails($domainId, PricingGroup $pricingGroup, array $sortedProductIds) {
		$products = $this->productRepository->getOfferedByIds($domainId, $pricingGroup, $sortedProductIds);
		$sortedProducts = $this->productService->sortProductsByProductIds($products, $sortedProductIds);

		return $this->productDetailFactory->getDetailsForProducts($sortedProducts);
	}

	/**
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return string
	 */
	private function getCacheId($domainId, Category $category, PricingGroup $pricingGroup) {
		return $domainId . '_' . $category->getId() . '_' . $pricingGroup->getId();
	}

}
