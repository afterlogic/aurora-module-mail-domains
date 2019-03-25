<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
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

		$this->subscribeEvent('AdminPanelWebclient::GetEntityList::before', array($this, 'onBeforeGetEntityList'));
		$this->subscribeEvent('AdminPanelWebclient::CreateUser::after', array($this, 'onAfterAdminPanelCreateUser'));
		
		$this->subscribeEvent('Core::CreateUser::after', array($this, 'onAfterCreateUser'));
		$this->subscribeEvent('Core::DeleteTenant::after', array($this, 'onAfterDeleteTenant'));
		
		$this->subscribeEvent('Mail::UpdateServer::after', array($this, 'onAfterUpdateServer'));
		$this->subscribeEvent('Mail::ServerToResponseArray', array($this, 'onServerToResponseArray'));
		$this->subscribeEvent('Mail::GetServerByDomain', array($this, 'onGetServerByDomain'));
		
		\Aurora\Modules\Core\Classes\User::extend(
			self::GetName(),
			[
				'DomainId' => array('int', 0)
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
		$oMailModule = \Aurora\System\Api::GetModule('Mail');
		return $oMailModule->getServersManager();
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
		
		$oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantById($TenantId);
		$oServer = $this->getServersManager()->getServer($MailServerId);
		if (!$oTenant || !$oServer || $DomainName === '')
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		$mResult = $this->getDomainsManager()->createDomain($TenantId, $MailServerId, $DomainName);

		return $mResult;
	}
	
	public function onGetServerByDomain($aArgs, &$mResult)
	{
		$oTenant = \Aurora\System\Api::getTenantByWebDomain();
		$oDomain = $this->getDomainsManager()->getDomainByName($aArgs['Domain'], $oTenant ? $oTenant->EntityId : 0);
		if ($oDomain)
		{
			$mResult = $this->getServersManager()->getServer($oDomain->MailServerId);
		}
	}
	
	public function onServerToResponseArray($aArgs, &$mResult)
	{
		if (is_array($mResult))
		{
			// $mResult['AllowToDelete']
			$mResult['AllowEditDomains'] = false;
			
			$aDomains = $this->getDomainsManager()->getDomainsNames($mResult['EntityId']);
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		
		if ($TenantId === 0)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		$aResult = [];
		$aDomains = $this->getDomainsManager()->getDomains($TenantId, $Offset, $Limit, $Search);
		$iDomainsCount = $this->getDomainsManager()->getDomainsCount($TenantId, $Search);
		foreach ($aDomains as $oDomain)
		{
			$oDomain->Count = \Aurora\Modules\Core\Module::Decorator()->getUsersManager()->getUsersCount('', [self::GetName() . '::DomainId' => $oDomain->EntityId]);
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
			foreach ($aUsers as $oUser)
			{
				\Aurora\Modules\Core\Module::Decorator()->DeleteUser($oUser->EntityId);
			}
			
			// delete domain
			$mResult = $this->getDomainsManager()->deleteDomain($iDomainId);
		}
		return $mResult;
	}
	
	public function onAfterAdminPanelCreateUser(&$aData, &$mResult)
	{
		$oUser = \Aurora\System\Api::getUserById($mResult);
		$oDomain = $oUser ? $this->getDomainsManager()->getDomain($oUser->{self::GetName() . '::DomainId'}) : null;
		$oServer = $oDomain ? $this->getServersManager()->getServer($oDomain->MailServerId) : null;
		if ($oServer)
		{
			\Aurora\Modules\Mail\Module::Decorator()->CreateAccount($oUser->EntityId, '', $aData['PublicId'], $aData['PublicId'], $aData['Password'], $oServer->toResponseArray());
		}
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
	}
	
	public function onAfterUpdateServer($aArgs, &$mResult)
	{
		$iServerId = $aArgs['ServerId'];
		$iTenantId = $aArgs['TenantId'];
		$sDomains = $aArgs['Domains'];
		
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
			if (strpos($sDomains, '*') === false)
			{
				$oServer->Domains = '';
			}
			else
			{
				$oServer->Domains = '*';
			}
			$this->getServersManager()->updateServer($oServer);
		}
	}
	
	public function onAfterDeleteTenant($aArgs, &$mResult)
	{
		$TenantId = $aArgs['TenantId'];
		
		$aDomains = $this->Decorator()->GetDomains($TenantId);
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
			$this->Decorator()->DeleteDomains($TenantId, $aDomainIds);
		}
	}

	public function onBeforeGetEntityList(&$aArgs, &$mResult)
	{
		if ($aArgs['Type'] === 'User' && isset($aArgs['DomainId']) && $aArgs['DomainId'] !== 0)
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
}
