<?php
/*
 * @package     RadicalMart 1C Integration
 * @subpackage  plg_radicalmart_1c
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMart\APL\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class APL extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Plugins forms path.
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $formsPath = JPATH_PLUGINS . '/radicalmart/apl/forms';

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartPrepareConfigForm'  => 'loadConfigForm',
			'onRadicalMartPrepareProductForm' => 'loadProductForm',
			'onRadicalMartGetCartProduct'     => 'prepareCartProduct',
			'onRadicalMartGetOrder'           => 'prepareOrderObject',
		];
	}

	/**
	 * Method to load RadicalMart & RadicalMart Express configuration form.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function loadConfigForm(Form $form, $data = [])
	{
		$form->loadFile($this->formsPath . '/config.xml');
	}

	/**
	 * Method to load RadicalMart & RadicalMart Express product form.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function loadProductForm(Form $form, $data = [])
	{
		$form->loadFile($this->formsPath . '/product.xml');
	}

	/**
	 * Method to remove links from cart RadicalMart cart.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   string|null  $key      Cart product key.
	 * @param   object|null  $product  Cart product data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function prepareCartProduct(?string $context = null, ?string &$key = null, ?object &$product = null)
	{
		if (!empty($product) && !empty($product->plugins) && !empty($product->plugins->get('apl')))
		{
			$product->plugins->remove('apl');
		}
	}

	/**
	 * Method to remove links from cart RadicalMart cart.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $order    Order object data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function prepareOrderObject(?string $context = null, ?object &$order = null)
	{
		if (empty($order) || empty($order->products))
		{
			return;
		}

		$statuses = false;
		if (strpos($context, 'com_radicalmart_express.') !== false)
		{
			$statuses = ComponentHelper::getParams('com_radicalmart_express')->get('apl_statuses', []);
		}
		elseif (strpos($context, 'com_radicalmart.') !== false)
		{
			$statuses = ComponentHelper::getParams('com_radicalmart')->get('apl_statuses', []);
		}

		if (empty($statuses) && !is_array($statuses))
		{
			return;
		}

		$statuses = ArrayHelper::toInteger($statuses);
		$display  = (!empty($order->status) && !empty($order->status->id) && in_array((int) $order->status->id, $statuses));

		foreach ($order->products as &$product)
		{
			if (!empty($product->plugins) && !empty($product->plugins->get('apl')))
			{
				if ($display)
				{
					$links = (new Registry($product->plugins->get('apl')))->toArray();
					foreach ($links as $link)
					{
						if (empty($link['href']))
						{
							continue;
						}

						$target = (!empty($link['target'])) ? ' target="' . $link['target'] . '"' : '';
						$text   = (!empty($link['text'])) ? $link['text'] : $link['href'];
						$class  = (!empty($link['class'])) ? ' class="' . $link['class'] . '"' : '';

						$product->extra_display[] = [
							'type' => 'apl',
							'data' => $link,
							'html' => '<a href="' . $link['href'] . '"' . $target . $class . '>' . $text . '</a>',
						];
					}
				}
				else
				{
					$product->plugins->remove('apl');
				}
			}
		}
	}
}