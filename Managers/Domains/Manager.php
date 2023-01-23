<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains\Managers\Domains;

use Aurora\Modules\MailDomains\Models\Domain;
use Aurora\System\Enums\SortOrder;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package MailDomains
 * @subpackage Managers
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
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

		$oDomain = new Domain();
		$oDomain->TenantId = $iTenantId;
		$oDomain->MailServerId = $iMailServerId;
		$oDomain->Name = $sDomainName;
		$oDomain->save();

		return $oDomain->Id;
	}

	/**
	 * @param int $iTenantId Tenant identifier.
	 * @param string $sSearch Search string.
	 * @return int|false
	 */
	public function getDomainsCount($iTenantId, $sSearch)
	{
		$query = Domain::where('TenantId', $iTenantId);
		if (!empty($sSearch))
		{
			$query->where('Name', 'like', '%' . $sSearch . '%');
		}

		return $query->count();
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
		$query = Domain::where('TenantId', $iTenantId);
		if (!empty($sSearch))
		{
			$query->where('Name', 'like', '%' . $sSearch . '%');
		}
		if ($iOffset > 0)
		{
			$query->offset($iOffset);
		}
		if ($iLimit > 0)
		{
			$query->limit($iLimit);
		}
		return $query->orderBy('Name', 'asc')->get();
	}

	/**
	 * Obtains all mail domains that belong to the specified mail server.
	 * @param int $iMailServerId
	 * @return Array|false
	 */
	public function getDomainsByMailServerId($iMailServerId, $iTenantId = null)
	{
		$query = Domain::where('MailServerId', $iMailServerId);
		if (is_numeric($iTenantId))
		{
			$query->where('TenantId', $iTenantId);
		}

		return $query->get();
	}

	/**
	 * Obtains all domains.
	 * @param int $iOffset Offset of the list.
	 * @param int $iLimit Limit of the list.
	 * @return array|boolean
	 */
	public function getFullDomainsList($iOffset = 0, $iLimit = 0)
	{
		$query = Domain::query();
		if ($iOffset > 0)
		{
			$query->offset($iOffset);
		}
		if ($iLimit > 0)
		{
			$query->limit($iLimit);
		}
		return $query->orderBy('Name', 'asc')->get();
	}

	/**
	 * Obtains all domains names for specified mail server.
	 * @param int $iMailServerId Mail server identifier.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomainsNames($iMailServerId, $iTenantId = null)
	{
		$query = Domain::where('MailServerId', $iMailServerId);
		if (is_numeric($iTenantId))
		{
			$query->where('TenantId', $iTenantId);
		}
		$oDomains = $query->orderBy('Name', 'asc')->get();

		return $oDomains->map(function ($oDomain) {
			return $oDomain->Name;
		})->toArray();
	}

	/**
	 * Obtains specified domain.
	 * @param int $iDomainId Domain identifier.
	 * @return array|boolean
	 */
	public function getDomain($iDomainId)
	{
		return Domain::find($iDomainId);
	}

	/**
	 * Deletes domain.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function deleteDomain($iDomainId)
	{
		return Domain::find($iDomainId)->delete();
	}

	/**
	 * Obtains specified domain.
	 * @param string $sDomainName Domain name.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomainByName($sDomainName, $iTenantId)
	{
		$query = Domain::where('Name', $sDomainName);
		if ($iTenantId !== 0)
		{
			$query = $query->where('TenantId', $iTenantId);
		}
		return $query->orderBy('Name', 'asc')->first();
	}
}
