<?php
/**
 * This code is licensed under AfterLogic Software License.
 * For full statements of the license see LICENSE file.
 */

namespace Aurora\Modules\MailDomains\Managers\Domains;

/**
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package MailDomains
 * @subpackage Managers
 */
class Manager extends \Aurora\System\Managers\AbstractManagerWithStorage
{
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule, new \Aurora\Modules\MailDomains\Managers\Domains\Storages\db\Storage($this));
	}

	/**
	 * Creates domain.
	 * @param int $iTenantId Tenant identifier.
	 * @param int $sDomainName Domain name.
	 * @return boolean
	 */
	public function createDomain($iTenantId, $iMailServerId, $sDomainName)
	{
		if ($this->getDomainByName($sDomainName, 0)) // domains should be unique for entire system (not only for tenant)
		{
			throw new \Aurora\Modules\MailDomains\Exceptions\Exception(\Aurora\Modules\MailDomains\Enums\ErrorCodes::DomainExists);
		}
		return $this->oStorage->createDomain($iTenantId, $iMailServerId, $sDomainName);
	}
	
	/**
	 * Obtains all domains for specified tenant.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomains($iTenantId)
	{
		return $this->oStorage->getDomains($iTenantId);
	}
	
	/**
	 * Obtains all domains names for specified mail server.
	 * @param int $iMailServerId Mail server identifier.
	 * @return array|boolean
	 */
	public function getDomainsNames($iMailServerId)
	{
		return $this->oStorage->getDomainsNames($iMailServerId);
	}
	
	/**
	 * Obtains specified domain.
	 * @param int $iDomainId Domain identifier.
	 * @return array|boolean
	 */
	public function getDomain($iDomainId)
	{
		return $this->oStorage->getDomain($iDomainId);
	}
	
	/**
	 * Deletes domain.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function deleteDomain($iDomainId)
	{
		return $this->oStorage->deleteDomain($iDomainId);
	}

	/**
	 * Get domain member emails list.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function getDomainMembers($iDomainId)
	{
		return $this->oStorage->getDomainMembers($iDomainId);
	}

	/**
	 * Obtains specified domain.
	 * @param string $sDomainName Domain name.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomainByName($sDomainName, $iTenantId)
	{
		return $this->oStorage->getDomainByName($sDomainName, $iTenantId);
	}
	
	/**
	 * Creates tables required for module work by executing create.sql file.
	 *
	 * @return boolean
	 */
	public function createTablesFromFile()
	{
		$sFilePath = dirname(__FILE__) . '/Storages/db/Sql/create.sql';
		$bResult = \Aurora\System\Managers\Db::getInstance()->executeSqlFile($sFilePath);
		
		return $bResult;
	}
}
