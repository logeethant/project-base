<?php

namespace SS6\ShopBundle\Form\Front\Cart;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class CartFormType extends AbstractType {

	/**
	 * @var \SS6\ShopBundle\Model\Cart\Cart
	 */
	private $cart;

	public function __construct(\SS6\ShopBundle\Model\Cart\Cart $cart) {
		$this->cart = $cart;
	}

		/**
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder
			->add('quantities', 'collection', array(
				'type' => 'integer',
				'constraints' => array(
						new Constraints\All(array(
							'constraints' => array(
								new Constraints\NotBlank(array('message' => 'Musíte zadat množství kusů zboží')),
								new Constraints\GreaterThan(array('value' => 0, 'message' => 'Musíte zadat množství kusů zboží')),
							),
						))
					),
				))
			->add('recalc', 'submit')
			->add('recalcToOrder', 'submit');
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'cart';
	}

	/**
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'attr' => array('novalidate' => 'novalidate'),
		));
	}

}