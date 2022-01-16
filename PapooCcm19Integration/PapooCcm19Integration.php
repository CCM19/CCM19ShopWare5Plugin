<?php
/*
 * Papoo CCM19 Integration Plugin
 * Version 1.0.4
 *
 * (c) Papoo Software & Media GmbH
 *     Dr. Carsten Euwens
 *
 * @copyright Papoo Software & Media GmbH
 * @author Christoph Grenz <info@papoo.de>
 * @date 2021-04-14
 */

namespace PapooCcm19Integration;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PapooCcm19Integration extends Plugin
{

	/**
	 * {@inheritdoc}
	 */
	public function activate(ActivateContext $activateContext)
	{
		$activateContext->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
	}

}
