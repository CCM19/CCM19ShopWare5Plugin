<?php
/*
 * @copyright Papoo Software & Media GmbH
 * @author Christoph Grenz <info@papoo.de>
 * @date 2021-04-14
 */

namespace PapooCcm19Integration\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Components\Plugin\ConfigReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Enlight_Template_Manager;

/**
 * Subscriber for frontend events
 */
class Frontend implements SubscriberInterface
{
	/** @var array */
	private $config;

	/** @var Enlight_Template_Manager */
	private $templateManager;


	/**
	 * Initialize subscriber and read configuration
	 *
	 * @param CachedConfigReader|ConfigReader $configReader
	 * @param ContainerInterface $container
	 */
	public function __construct(ConfigReader $configReader, ContainerInterface $container, Enlight_Template_Manager $templateManager)
	{
		if ($container->initialized('shop')) {
			$shop = $container->get('shop');
		}

		if (!$shop) {
			$shop = $container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
		}

		$this->config = $configReader->getByPluginName('PapooCcm19Integration', $shop);
		$this->templateManager = $templateManager;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
			'Enlight_Controller_Action_PostDispatch_Frontend' => 'onPostDispatch',
		];
	}

	/**
	 * @return string|null
	 */
	private function getIntegrationUrl()
	{
		if (!empty($this->config['integrationCode'])) {
			$code = (string)$this->config['integrationCode'];
			$match = [];
			preg_match('~\bhttps?://[^"\'\s]{1,256}\.js\?(?>[^"\'\s]{1,128})~i', $code, $match);
			if ($match and $match[0]) {
				if (strpos($match[0], ';') === false) {
					return $match[0];
				} else {
					return html_entity_decode($match[0], ENT_HTML401|ENT_QUOTES, 'UTF-8');
				}
			}
		}
		return null;
	}

	/**
	 * Hook: add templates
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return void
	 */
	public function onPreDispatch(\Enlight_Event_EventArgs $args)
	{
		$this->templateManager->addTemplateDir(__DIR__ . '/../Resources/Views');
	}

	/**
	 * Hook: title and description in ATSD Product Stream pages
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return void
	 */
	public function onPostDispatch(\Enlight_Event_EventArgs $args)
	{
		$view = $args->getSubject()->View();
		$view->assign('ccm19IntegrationUrl', $this->getIntegrationUrl());
		$view->extendsTemplate('frontend/index/header_ccm19.tpl');
	}
}
