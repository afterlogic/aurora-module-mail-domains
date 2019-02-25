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
		if ($DomainName === '' || !$oServer)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		if ($TenantId === 0)
		{
			$TenantId = $this->getSingleDefaultTenantId();
		}
		
		$mResult = $this->oApiDomainsManager->createDomain($TenantId, $MailServerId, $DomainName);

		return $mResult;
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
			$TenantId = $this->getSingleDefaultTenantId();
		}
		$aDomains = $this->oApiDomainsManager->getDomains($TenantId);
		if (is_array($aDomains))
		{
			return [
				'Count' => count($aDomains),
				'Items' => $aDomains
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
		
		return $this->oApiDomainsManager->getDomain($Id);
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
			// deleteDomainMembers
			$aUsers = \Aurora\Modules\Core\Module::Decorator()->GetUserList(0, 0, 'PublicId', \Aurora\System\Enums\SortOrder::ASC, '', [self::GetName() . '::DomainId' => $iDomainId]);
			var_dump($aUsers);
			exit;
			$aDomainMemberEmails = $this->oApiDomainsManager->getDomainMembers($iDomainId);
			$oDomain = $this->oApiDomainsManager->getDomain($iDomainId);
			foreach ($aDomainMemberEmails as $aMember)
			{
				if ($aMember['UserId'] > 0)
				{
					\Aurora\Modules\Core\Module::Decorator()->DeleteUser((int) $aMember['UserId']);
				}
			}
			$mResult = $this->oApiDomainsManager->deleteDomain($iDomainId);
			if ($mResult)
			{
				// remove domain from server domains list
				$oServer = $this->getServersManager()->getServerByDomain($oDomain['Name']);
				if ($oServer instanceof \Aurora\Modules\Mail\Classes\Server)
				{
					$aDomainsNames = explode("\r\n", $oServer->Domains);
					$iIndex = array_search($oDomain['Name'], $aDomainsNames);
					if ($iIndex !== false)
					{
						array_splice($aDomainsNames, $iIndex, 1);
						$oServer->Domains = count($aDomainsNames) === 0 ? '*' : join("\r\n", $aDomainsNames);
						$this->getServersManager()->updateServer($oServer);
					}
				}
			}
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
