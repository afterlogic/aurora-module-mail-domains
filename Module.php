<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	/*
	 * @var $oApiDomainsManager Managers\MailingLists
	 */
	public $oApiDomainsManager = null;

	public function init()
	{
		$this->aErrors = [
			Enums\ErrorCodes::DomainExists	=> $this->i18N('ERROR_DOMAIN_EXISTS')
		];

		$this->subscribeEvent('AdminPanelWebclient::GetEntityList::before', array($this, 'onBeforeGetEntityList')); /** @deprecated since version 8.3.7 **/
		$this->subscribeEvent('Core::GetUsers::before', array($this, 'onBeforeGetUsers'));
		$this->subscribeEvent('AdminPanelWebclient::CreateUser::after', array($this, 'onAfterAdminPanelCreateUser')); /** @deprecated since version 8.3.7 **/
		$this->subscribeEvent('Core::CreateUser::after', array($this, 'onAfterCreateUser'));
		$this->subscribeEvent('Core::DeleteTenant::after', array($this, 'onAfterDeleteTenant'));

		$this->subscribeEvent('Mail::UpdateServer::after', array($this, 'onAfterUpdateServer'));
		$this->subscribeEvent('Mail::DeleteServer::before', array($this, 'onBeforeDeleteServer'));
		$this->subscribeEvent('Mail::ServerToResponseArray', array($this, 'onServerToResponseArray'));
		$this->subscribeEvent('Mail::GetMailServerByDomain::before', array($this, 'onBeforeGetMailServerByDomain'));
		$this->subscribeEvent('GetMailDomains', [$this, 'onGetMailDomains']);
		$this->subscribeEvent('Mail::GetServerDomains::after', [$this, 'onAfterGetMailDomains']);

		\Aurora\Modules\Core\Classes\User::extend(
			self::GetName(),
			[
				'DomainId' => array('int', 0, true)
			]
		);
	}

	public function getDomainsManager()
	{
		if ($this->oApiDomainsManager === null)
		{
			$this->oApiDomainsManager = new Managers\Domains\Manager($this);
		}

		return $this->oApiDomainsManager;
	}

	protected function getServersManager()
	{
		return \Aurora\Modules\Mail\Module::getInstance()->getServersManager();
	}

	/**
	 * Creates domain.
	 * @param int $TenantId Tenant identifier.
	 * @param int $DomainName Domain name.
	 * @return boolean
	 */
	public function CreateDomain($TenantId = 0, $MailServerId = 0, $DomainName = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

		$oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantUnchecked($TenantId);
		$oServer = $this->getServersManager()->getServer($MailServerId);
		if (!$oTenant || !$oServer || \trim($DomainName) === '')
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$mResult = $this->getDomainsManager()->createDomain($TenantId, $MailServerId, \trim($DomainName));

		return $mResult;
	}

	public function onBeforeGetMailServerByDomain($aArgs, &$mResult)
	{
		$mResult = [];

		if (isset($aArgs['Domain']))
		{
			$oTenant = \Aurora\System\Api::getTenantByWebDomain();
			$oDomain = $this->getDomainsManager()->getDomainByName($aArgs['Domain'], $oTenant ? $oTenant->EntityId : 0);
			if ($oDomain)
			{
				$oServer = $this->getServersManager()->getServer($oDomain->MailServerId);
				if ($oServer instanceof \Aurora\Modules\Mail\Classes\Server)
				{
					$mResult = [
						'Server'			=> $oServer,
						'FoundWithWildcard'	=> false
					];
				}
			}
		}

		return true; // break subscriptions to prevent returning servers from other modules
	}

	public function onServerToResponseArray($aArgs, &$mResult)
	{
		$iTenantId = null;
		$oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oAuthenticatedUser instanceof \Aurora\Modules\Core\Classes\User)
		{
			if ($oAuthenticatedUser->Role === \Aurora\System\Enums\UserRole::NormalUser || $oAuthenticatedUser->Role === \Aurora\System\Enums\UserRole::TenantAdmin)
			{
				$iTenantId = $oAuthenticatedUser->IdTenant;
			}
		}
		if (is_array($mResult))
		{
			// $mResult['AllowToDelete']
			$mResult['AllowEditDomains'] = false;

			$aDomains = $this->getDomainsManager()->getDomainsNames($mResult['EntityId'], $iTenantId);
			$sDomains = join("\r\n", $aDomains);
			if (strpos($mResult['Domains'], '*') !== false)
			{
				$sDomains = "*\r\n" . $sDomains;
			}
			$mResult['Domains'] = $sDomains;
		}
	}

	/**
	 * Obtains all domains for specified tenant.
	 * @param int $TenantId Tenant identifier.
	 * @param int $Offset Offset of the list.
	 * @param int $Limit Limit of the list.
	 * @param string $Search Search string.
	 * @return array|boolean
	 */
	public function GetDomains($TenantId = 0, $Offset = 0, $Limit = 0, $Search = '')
	{
		$oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oAuthenticatedUser instanceof \Aurora\Modules\Core\Classes\User && $oAuthenticatedUser->Role === \Aurora\System\Enums\UserRole::TenantAdmin && $oAuthenticatedUser->IdTenant === $TenantId)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}

		if ($TenantId === 0)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$aResult = [];
		$aDomains = $this->getDomainsManager()->getDomainsByTenantId($TenantId, $Offset, $Limit, $Search);
		$iDomainsCount = $this->getDomainsManager()->getDomainsCount($TenantId, $Search);
		foreach ($aDomains as $oDomain)
		{
			$oDomain->Count = \Aurora\Modules\Core\Module::getInstance()->getUsersManager()->getUsersCount('', [self::GetName() . '::DomainId' => $oDomain->EntityId]);
			$oDomain->Name = \trim($oDomain->Name);
			$aResult[] = $oDomain;
		}
		if (is_array($aDomains))
		{
			return [
				'Count' => $iDomainsCount,
				'Items' => $aResult
			];
		}

		return false;
	}

	/**
	 * Obtains domain.
	 * @param int $Id Domain identifier.
	 * @return array|boolean
	 */
	public function GetDomain($Id)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

		$oDomain = $this->getDomainsManager()->getDomain($Id);
		$oDomain->Count = \Aurora\Modules\Core\Module::Decorator()->getUsersManager()->getUsersCount('', [self::GetName() . '::DomainId' => $Id]);

		return $oDomain;
	}

	/**
	 * Deletes domains.
	 * @param int $TenantId Identifier of tenant which contains domains from list.
	 * @param int $IdList List of domain identifiers.
	 * @return boolean
	 */
	public function DeleteDomains($TenantId, $IdList)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		$mResult = false;
		foreach ($IdList as $iDomainId)
		{
			// delete domain users
			$aUsers = \Aurora\Modules\Core\Module::Decorator()->getUsersManager()->getUserList(0, 0, 'PublicId', \Aurora\System\Enums\SortOrder::ASC, '', [self::GetName() . '::DomainId' => $iDomainId]);
			foreach ($aUsers as $aUser)
			{
				\Aurora\Modules\Core\Module::Decorator()->DeleteUser($aUser['EntityId']);
			}

			// delete domain
			$mResult = $this->getDomainsManager()->deleteDomain($iDomainId);
		}
		return $mResult;
	}

	/**
	 * @deprecated since version 8.3.7
	 */
	public function onAfterAdminPanelCreateUser(&$aData, &$mResult)
	{
		$this->onAfterCreateUser($aData, $mResult);
	}

	public function onAfterCreateUser(&$aData, &$mResult)
	{
		$sEmail = isset($aData['PublicId']) ? $aData['PublicId'] : '';
		$sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
		$oDomain = $this->getDomainsManager()->getDomainByName($sDomain, $aData['TenantId']);
		$oUser = \Aurora\System\Api::getUserById($mResult);
		if ($oDomain && $oUser)
		{
			$oUser->{self::GetName() . '::DomainId'} = $oDomain->EntityId;
			$oUser->IdTenant = $oDomain->TenantId;
			\Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
		}

		if (isset($aData['Password']))
		{
			$oServer = $oDomain ? $this->getServersManager()->getServer($oDomain->MailServerId) : null;
			if ($oServer)
			{
				try
				{
					\Aurora\Modules\Mail\Module::Decorator()->CreateAccount($oUser->EntityId, '', $aData['PublicId'], $aData['PublicId'], $aData['Password'], $oServer->toResponseArray());
				}
				catch(\Exception $oException)
				{
					\Aurora\Modules\Core\Module::Decorator()->DeleteUser($oUser->EntityId);
					throw $oException;
				}
			}
			else
			{
				\Aurora\Modules\Core\Module::Decorator()->DeleteUser($oUser->EntityId);
			}
		}
	}

	public function onAfterUpdateServer($aArgs, &$mResult)
	{
		$iServerId = $aArgs['ServerId'];
		$iTenantId = $aArgs['TenantId'];

		$oServer = $this->getServersManager()->getServer($iServerId);

		if ($oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		}

		if ($oServer && ($oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin ||
				$oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant && $oServer->TenantId === $iTenantId))
		{
			$oServer->Domains = '';
			$this->getServersManager()->updateServer($oServer);
		}
	}

	/**
	 * Removes all mail domains that belong to the specified mail server.
	 * @param array $aArgs
	 * @param mixed $mResult
	 */
	public function onBeforeDeleteServer($aArgs, &$mResult)
	{
		$mDomains = $this->getDomainsManager()->getDomainsByMailServerId($aArgs['ServerId']);
		if (is_array($mDomains))
		{
			foreach ($mDomains as $oDomain)
			{
				self::Decorator()->DeleteDomains($oDomain->TenantId, [$oDomain->EntityId]);
			}
		}
	}

	public function onAfterDeleteTenant($aArgs, &$mResult)
	{
		$TenantId = $aArgs['TenantId'];

		$aDomains = self::Decorator()->GetDomains($TenantId);
		$aDomainIds = [];
		if (isset($aDomains['Items']) && is_array($aDomains['Items']))
		{
			foreach ($aDomains['Items'] as $oDomain)
			{
				if ($oDomain->TenantId === $TenantId)
				{
					$aDomainIds[] = $oDomain->EntityId;
				}
			}
		}
		if (count($aDomainIds))
		{
			self::Decorator()->DeleteDomains($TenantId, $aDomainIds);
		}
	}

	/**
	 * @deprecated since version 8.3.7
	 */
	public function onBeforeGetEntityList(&$aArgs, &$mResult)
	{
		if ($aArgs['Type'] === 'User')
		{
			$this->onBeforeGetUsers($aArgs, $mResult);
		}
	}

	public function onBeforeGetUsers(&$aArgs, &$mResult)
	{
		if (isset($aArgs['DomainId']) && $aArgs['DomainId'] !== -1)
		{
			if (isset($aArgs['Filters']) && is_array($aArgs['Filters']) && count($aArgs['Filters']) > 0)
			{
				$aArgs['Filters'][self::GetName() . '::DomainId'] = [$aArgs['DomainId'], '='];
				$aArgs['Filters'] = [
					'$AND' => $aArgs['Filters']
				];
			}
			else
			{
				$aArgs['Filters'] = [self::GetName() . '::DomainId' => [$aArgs['DomainId'], '=']];
			}
		}
	}

	public function onGetMailDomains($aArgs, &$mResult)
	{
		if (isset($aArgs['TenantId']))
		{
			$aDomains = $this->getDomainsManager()->getDomainsByTenantId($aArgs['TenantId']);
			$mResult = array_map(function ($oDomain) {
				return $oDomain->Name;
			}, $aDomains);
		}
		else
		{//get all domains for all tenants
			$aDomains = $this->getDomainsManager()->getFullDomainsList();
			$mResult = array_map(function ($oDomain) {
				return $oDomain->Name;
			}, $aDomains);
		}
	}


	public function onAfterGetMailDomains($aArgs, &$mResult)
	{
		if (isset($aArgs['ServerId']))
		{
			$aDomains = $this->getDomainsManager()->getDomainsByMailServerId($aArgs['ServerId'], isset($aArgs['TenantId']) ? $aArgs['TenantId'] : null);
			$mResult = array_map(function ($oDomain) {
				return $oDomain->Name;
			}, $aDomains);
		}
	}
}
