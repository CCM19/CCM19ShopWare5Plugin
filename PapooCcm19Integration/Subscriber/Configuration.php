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
use Shopware\Components\HttpClient\GuzzleFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use DateTime;
use DateTimeInterface;

/**
 * Subscriber for configuration and cronjob events
 */
class Configuration implements SubscriberInterface
{
	const API_TOKEN = 'xCHnQegWLeprUzBQFdPSsXAp6prvhaYI';

	/** @var CachedConfigReader|ConfigReader */
	private $configReader;
	/** @var \Shopware\Models\Shop\Repository */
	private $shopRepo;
	/** @var GuzzleFactory */
	private $clientFactory;

	private $kernel;

	/**
	 * Initialize subscriber
	 *
	 * @param CachedConfigReader|ConfigReader $configReader
	 * @param ContainerInterface $container
	 * @param GuzzleFactory $clientFactory
	 */
	public function __construct($configReader, ContainerInterface $container, GuzzleFactory $clientFactory)
	{
		$this->shopRepo = $container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class);
		$this->configReader = $configReader;
		$this->clientFactory = $clientFactory;
		$this->kernel = $container->get('kernel');
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'Shopware_Controllers_Backend_Config_After_Save_Config_Element' => 'onAfterSaveConfigElement',
			'Shopware_CronJob_CCM19_LicenseNotify' => 'onCronJob',
		];
	}

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @return array
	 */
	private function getPluginConfig($shop): array {
		$data = $this->configReader->getByPluginName('PapooCcm19Integration', $shop);
		return ($data) ? $data : [];
	}

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @return array{apiKey:string,domain:?string}|null
	 */
	private function getIntegrationApiKeyAndDomain($shop): ?array
	{
		$config = $this->getPluginConfig($shop);
		if ($config && isset($config['integrationCode'])) {
			$code = $this->getPluginConfig($shop)['integrationCode'];
			if ($code) {
				$keyMatch = [];
				$domainMatch = [];
				preg_match('~[?&;]apiKey=([^&\'"]*)~', $code, $keyMatch);
				preg_match('~[?&;]domain=([^&\'"]*)~', $code, $domainMatch);
				if ($keyMatch and $keyMatch[1]) {
					return [
						'apiKey' => html_entity_decode($keyMatch[1], ENT_HTML401|ENT_QUOTES, 'UTF-8'),
						'domain' => (empty($domainMatch[1])) ? null : html_entity_decode($domainMatch[1], ENT_HTML401|ENT_QUOTES, 'UTF-8'),
					];
				}
			}
		}
		return null;
	}

	/**
	 * Send license notification directly after changing the integration code
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return void
	 */
	public function onAfterSaveConfigElement(\Enlight_Event_EventArgs $args)
	{
		$element = $args->get('element');
		if (!$element or $element->getName() !== 'integrationCode') { return; }
		$form = $element->getForm();
		if (!$form or $form->getName() !== 'PapooCcm19Integration') { return; }

		$this->sendLicenseNotification();
	}

	/**
	 * Periodically send license notification
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return void
	 */
	public function onCronJob(\Enlight_Event_EventArgs $args)
	{
		$this->sendLicenseNotification();
	}

	/**
	 * @return string
	 */
	private function getShopwareVersion()
	{
		if ($this->kernel && method_exists($this->kernel, 'getRelease')) {
			$release = $this->kernel->getRelease();
			return $release['version'];
		} else {
			return \Shopware::VERSION;
		}
	}

	/**
	 * @return void
	 */
	private function sendLicenseNotification()
	{
		$result = [];
		$shops = $this->shopRepo->findByActive(true);
		foreach ($shops as $shop) {
			$data = $this->getIntegrationApiKeyAndDomain($shop);

			if ($data) {
				$result[$data['apiKey'].':'.$data['domain']] = $data;
			}
		}

		$now = new DateTime();
		$data = [
			'reportDate' => $now->format(DateTimeInterface::ATOM),
			'instanceId' => '',
			'shopwareVersion' => $this->getShopwareVersion(),
			'ccm19Data' => array_values($result),
		];
		$hash = $this->generateHash($data);

		$client = $this->clientFactory->createClient();
		$request = new Request(
			'POST',
				'https://licence.ccm19.de/shopware.php?action=report',
				['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => "Bearer $hash"],
				http_build_query($data, '', '&', PHP_QUERY_RFC1738)
		);
		$client->send($request);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private function generateHash($data) {
		ksort($data);
		$string = http_build_query($data, '', '&', PHP_QUERY_RFC1738);
		if (!$string) {
			$string = '';
		}
		return hash_hmac('sha256', $string, self::API_TOKEN);
	}
}
