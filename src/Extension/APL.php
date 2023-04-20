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
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  1.0.1
	 */
	protected $app = null;

	/**
	 * Plugins forms path.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected string $formsPath = JPATH_PLUGINS . '/radicalmart/apl/forms';

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartPrepareConfigForm'         => 'onPrepareConfigForm',
			'onRadicalMartPrepareProductForm'        => 'onPrepareProductForm',
			'onRadicalMartGetCartProduct'            => 'onCartProduct',
			'onRadicalMartGetOrder'                  => 'onGetOrder',
			'onRadicalMartExpressPrepareProductForm' => 'onPrepareProductForm',
			'onRadicalMartExpressGetOrder'           => 'onGetOrder',
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
	 * @since 1.0.1
	 */
	public function onPrepareConfigForm(Form $form, $data = [])
	{
		$component = $this->app->input->getCmd('component');
		if ($component === 'com_radicalmart')
		{
			$form->loadFile($this->formsPath . '/radicalmart/config.xml');
		}
	}

	/**
	 * Method to load RadicalMart & RadicalMart Express product form.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since 1.0.0
	 */
	public function onPrepareProductForm(Form $form, $data = [])
	{
		$formName = $form->getName();
		if ($formName === 'com_radicalmart.product')
		{
			$form->loadFile($this->formsPath . '/radicalmart/product.xml');
		}
		elseif ($formName === 'com_radicalmart_express.product')
		{
			$form->loadFile($this->formsPath . '/radicalmart_express/product.xml');
		}
	}

	/**
	 * Method to remove links from cart RadicalMart cart.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   string|null  $key      Cart product key.
	 * @param   object|null  $product  Cart product data.
	 *
	 * @since 1.0.0
	 */
	public function onCartProduct(?string $context = null, ?string &$key = null, ?object &$product = null)
	{
		if (!empty($product) && !empty($product->plugins) && !empty($product->plugins->get('apl')))
		{
			$product->plugins->remove('apl');
		}
	}

	/**
	 * Method to add or remove links in RadicalMart & RadicalMart Express order.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $order    Order object data.
	 *
	 * @since 1.0.0
	 */
	public function onGetOrder(?string $context = null, ?object &$order = null)
	{
		if (empty($order) || empty($order->products))
		{
			return;
		}

		$statuses = false;
		if (strpos($context, 'com_radicalmart_express.') !== false)
		{
			$statuses = [2];
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