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
 * @copyright Copyright (c) 2018, Afterlogic Corp.
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
			Enums\ErrorCodes::DomainExists	=> $this->i18N('ERROR_CONNECT_TO_MAIL_SERVER')
		];

		$this->subscribeEvent('AdminPanelWebclient::GetEntityList::before', array($this, 'onBeforeGetEntityList'));
		$this->subscribeEvent('Core::CreateUser::after', array($this, 'onAfterCreateUser'));
		$this->subscribeEvent('Mail::ServerToResponseArray', array($this, 'onServerToResponseArray'));
		$this->subscribeEvent('Mail::GetServerByDomain', array($this, 'onGetServerByDomain'));
		$this->subscribeEvent('Core::DeleteTenant::after', array($this, 'onAfterDeleteTenant'));
		
		$this->oApiDomainsManager = new Managers\Domains\Manager($this);

		\Aurora\Modules\Core\Classes\User::extend(
			self::GetName(),
			[
				'DomainId' => array('int', 0)
			]
		);		
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
		
		$oServer = $this->getServersManager()->getServer($MailServerId);
		
		if ($TenantId === 0 || $DomainName === '' || !$oServer)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		$mResult = $this->oApiDomainsManager->createDomain($TenantId, $MailServerId, $DomainName);

		return $mResult;
	}
	
	public function onGetServerByDomain($aArgs, &$mResult)
	{
		$oDomain = $this->oApiDomainsManager->getDomainByName($aArgs['Domain']);
		if ($oDomain)
		{
			$mResult = $this->getServersManager()->getServer($oDomain['MailServerId']);
		}
	}
	
	public function onServerToResponseArray($aArgs, &$mResult)
	{
		if (is_array($mResult))
		{
			// $mResult['AllowToDelete']
			$mResult['AllowEditDomains'] = false;
			$aDomains = $this->oApiDomainsManager->getDomainsNames($mResult['EntityId']);
			$mResult['Domains'] = join("\r\n", $aDomains);
		}
	}
	
	/**
	 * Obtains all domains for specified tenant.
	 * @param int $TenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function GetDomains($TenantId = 0)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		
		if ($TenantId === 0)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		$aResult = [];
		$aDomains = $this->oApiDomainsManager->getDomains($TenantId);
		foreach ($aDomains as $aDomain) {
			$aDomain['Count'] = \Aurora\Modules\Core\Module::Decorator()->getUsersManager()->getUsersCount('', [self::GetName() . '::DomainId' => $aDomain['Id']]);
			$aResult[] = $aDomain;
		}
		if (is_array($aDomains))
		{
			return [
				'Count' => count($aResult),
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
		
		$aDomain = $this->oApiDomainsManager->getDomain($Id);
		$aDomain['Count'] = \Aurora\Modules\Core\Module::Decorator()->getUsersManager()->getUsersCount('', [self::GetName() . '::DomainId' => $aDomain['Id']]);
		
		return $aDomain;
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
			$mResult = $this->oApiDomainsManager->deleteDomain($iDomainId);
		}
		return $mResult;
	}
	
	public function onAfterCreateTables(&$aData, &$mResult)
	{
		$this->oApiDomainsManager->createTablesFromFile();
	}
	
	public function onAfterCreateUser(&$aData, &$mResult)
	{
		$sEmail = isset($aData['PublicId']) ? $aData['PublicId'] : '';
		$sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
		$oDomain = $this->oApiDomainsManager->getDomainByName($sDomain);
		$oUser = \Aurora\System\Api::getUserById($mResult);
		if ($oDomain && $oUser)
		{
			$oUser->{self::GetName() . '::DomainId'} = $oDomain['DomainId'];
			$oUser->IdTenant = $oDomain['TenantId'];
			\Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
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
				if ($oDomain['TenantId'] === $TenantId)
				{
					$aDomainIds[] = $oDomain['Id'];
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
