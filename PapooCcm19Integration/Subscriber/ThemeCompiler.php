<?php
/*
 * @copyright Papoo Software & Media GmbH
 * @author Christoph Grenz <info@papoo.de>
 * @date 2021-04-14
 */

namespace PapooCcm19Integration\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Components\Plugin\ConfigReader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subscriber for theme compiler events for script blocking
 */
class ThemeCompiler implements SubscriberInterface
{
	/** @var array */
	private $config;

	/** @var string[] */
	private $BLOCKED_JS = [
		'SwagGoogle/Resources/frontend/js/jquery.google_analytics_plugin.js'
	];

	/**
	 * Initialize subscriber and read configuration
	 *
	 * @param CachedConfigReader|ConfigReader $configReader
	 * @param ContainerInterface $container
	 */
	public function __construct(ConfigReader $configReader, ContainerInterface $container)
	{
		$shop = ($container->initialized('shop')) ? $container->get('shop') : null;

		if (!$shop) {
			$shop = $container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
		}

		$this->config = $configReader->getByPluginName('PapooCcm19Integration', $shop);
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'Theme_Compiler_Collect_Javascript_Files_FilterResult' => 'modifyJsFiles',
			'Theme_Compiler_Collect_Plugin_Javascript' => ['addJsFiles', 100],
		];
	}

	/**
	 * Callback for array_filter()
	 *
	 * @param string $item
	 * @return bool
	 */
	public function filterJsList($item)
	{
		foreach ($this->BLOCKED_JS as $blocked) {
			if (stripos($item, $blocked) !== false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * This hook prevents the automatic loading of GA code, if this
	 * option is activated.
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return string[]
	 */
	public function modifyJsFiles(Enlight_Event_EventArgs $args)
	{
		/** @var string[] $array */
		$array = $args->getReturn();

		if (!empty($this->config['blockGA'])) {
			$array = array_filter($array, [$this, 'filterJsList']);
		}

		return $array;
	}

	/**
	 * This hook adds a compatibility layer for plugins using the Shopware
	 * Cookie Manager.
	 * @return ArrayCollection
	 */
	public function addJsFiles(Enlight_Event_EventArgs $args)
	{
		$jsDir = dirname(__DIR__).'/Resources/Views/frontend/_public/src/js/';
		return new ArrayCollection([
            $jsDir . 'ccm19-integration.js',
        ]);
	}
}
