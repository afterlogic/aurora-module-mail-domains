<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains\Managers\Domains;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package MailDomains
 * @subpackage Managers
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var \Aurora\System\Managers\Eav
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
		
		$this->oEavManager = \Aurora\System\Managers\Eav::getInstance();
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
		
		$oDomain = new \Aurora\Modules\MailDomains\Classes\Domain(\Aurora\Modules\MailDomains\Module::GetName());
		$oDomain->TenantId = $iTenantId;
		$oDomain->MailServerId = $iMailServerId;
		$oDomain->Name = $sDomainName;
		$this->oEavManager->saveEntity($oDomain);
		
		return $oDomain->EntityId;
	}
	
	/**
	 * @param int $iTenantId Tenant identifier.
	 * @param string $sSearch Search string.
	 * @return int|false
	 */
	public function getDomainsCount($iTenantId, $sSearch)
	{
		$aFilters = [
			'TenantId' => [$iTenantId, '='],
			'Name' => ['%' . $sSearch . '%', 'LIKE'],
		];
		
		return $this->oEavManager->getEntitiesCount(
			\Aurora\Modules\CoreUserGroups\Classes\Group::class,
			$aFilters
		);
	}
	
	/**
	 * Obtains all domains for specified tenant.
	 * @param int $iTenantId Tenant identifier.
	 * @param int $iOffset Offset of the list.
	 * @param int $iLimit Limit of the list.
	 * @param string $sSearch Search string.
	 * @return array|boolean
	 */
	public function getDomainsByTenantId($iTenantId, $iOffset = 0, $iLimit = 0, $sSearch = '')
	{
		$aFilters = [
			'TenantId' => [$iTenantId, '='],
			'Name' => ['%' . $sSearch . '%', 'LIKE']
		];
		$sOrderBy = 'Name';
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;
		
		return $this->oEavManager->getEntities(
			\Aurora\Modules\MailDomains\Classes\Domain::class,
			array(),
			$iOffset,
			$iLimit,
			$aFilters,
			$sOrderBy,
			$iOrderType
		);
	}

	/**
	 * Obtains all mail domains that belong to the specified mail server.
	 * @param int $iMailServerId
	 * @return Array|false
	 */
	public function getDomainsByMailServerId($iMailServerId)
	{
		$aFilters = [
			'MailServerId' => [$iMailServerId, '=']
		];
		
		return $this->oEavManager->getEntities(
			\Aurora\Modules\MailDomains\Classes\Domain::class,
			array(),
			0,
			0,
			$aFilters
		);
	}
	
	/**
	 * Obtains all domains.
	 * @param int $iOffset Offset of the list.
	 * @param int $iLimit Limit of the list.
	 * @return array|boolean
	 */
	public function getFullDomainsList($iOffset = 0, $iLimit = 0)
	{
		$sOrderBy = 'Name';
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;

		return $this->oEavManager->getEntities(
			\Aurora\Modules\MailDomains\Classes\Domain::class,
			array(),
			$iOffset,
			$iLimit,
			[],
			$sOrderBy,
			$iOrderType
		);
	}

	/**
	 * Obtains all domains names for specified mail server.
	 * @param int $iMailServerId Mail server identifier.
	 * @return array|boolean
	 */
	public function getDomainsNames($iMailServerId)
	{
		$iOffset = 0;
		$iLimit = 0;
		$aFilters = ['MailServerId' => [$iMailServerId, '=']];
		$sOrderBy = 'Name';
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;
		
		$aDomains = $this->oEavManager->getEntities(
			\Aurora\Modules\MailDomains\Classes\Domain::class,
			array('Name'),
			$iOffset,
			$iLimit,
			$aFilters,
			$sOrderBy,
			$iOrderType
		);
		$aDomainsNames = [];
		foreach ($aDomains as $oDomain)
		{
			$aDomainsNames[] = $oDomain->Name;
		}
		return $aDomainsNames;
	}
	
	/**
	 * Obtains specified domain.
	 * @param int $iDomainId Domain identifier.
	 * @return array|boolean
	 */
	public function getDomain($iDomainId)
	{
		return $this->oEavManager->getEntity($iDomainId, \Aurora\Modules\MailDomains\Classes\Domain::class);
	}
	
	/**
	 * Deletes domain.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function deleteDomain($iDomainId)
	{
		$bResult = false;
		$oDomain = $this->getDomain($iDomainId);
		if ($oDomain)
		{
			$bResult = $this->oEavManager->deleteEntity($iDomainId);
		}
		return $bResult;
	}

	/**
	 * Obtains specified domain.
	 * @param string $sDomainName Domain name.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomainByName($sDomainName, $iTenantId)
	{
		$iOffset = 0;
		$iLimit = 0;
		$aFilters = [];
		if ($iTenantId === 0)
		{
			$aFilters = ['Name' => [$sDomainName, '=']];
		}
		else
		{
			$aFilters = ['$AND' => [
				'TenantId' => [$iTenantId, '='],
				'Name' => [$sDomainName, '=']
			]];
		}
		$sOrderBy = 'Name';
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;
		
		$aDomains = $this->oEavManager->getEntities(
			\Aurora\Modules\MailDomains\Classes\Domain::class,
			array(),
			$iOffset,
			$iLimit,
			$aFilters,
			$sOrderBy,
			$iOrderType
		);
		
		return count($aDomains) > 0 ? $aDomains[0] : false;
	}
}
