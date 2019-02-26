<?php
/**
 * This code is licensed under AfterLogic Software License.
 * For full statements of the license see LICENSE file.
 */
namespace Aurora\Modules\MailDomains\Managers\Domains\Storages\db;
/**
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @internal
 */
class CommandCreator extends \Aurora\System\Db\AbstractCommandCreator
{
	/**
	 * Creates SQL-query to create domain.
	 * @param int $iTenantId Tenant identifier.
	 * @param int $sDomainName Domain name.
	 * @return string
	 */
	public function createDomain($iTenantId, $iMailServerId, $sDomainName)
	{
		$sSql = 'INSERT INTO awm_domains ( id_tenant, id_mail_server, name ) VALUES ( %d, %d, %s )';
		
		return sprintf($sSql,
			$iTenantId,
			$iMailServerId,
			$this->escapeString($sDomainName)
		);
	}
	
	/**
	 * Creates SQL-query to obtain all domains for specified tenant.
	 * @param int $iTenantId Tenant identifier.
	 * @return string
	 */
	public function getDomains($iTenantId)
	{
		$sSql = 'SELECT
				awm_domains.id_domain,
				awm_domains.id_tenant,
				awm_domains.id_mail_server,
				awm_domains.name
			FROM awm_domains
			WHERE awm_domains.id_tenant = %d
			GROUP BY awm_domains.id_domain';

		return sprintf($sSql, $iTenantId);
	}
	
	public function getDomainsNames($iMailServerId)
	{
		$sSql = 'SELECT awm_domains.name
			FROM awm_domains
			WHERE awm_domains.id_mail_server = %d';
		
		return sprintf($sSql, $iMailServerId);
	}
	
	/**
	 * Creates SQL-query to obtain domain with specified identifier.
	 * @param int $iDomainId Domain identifier.
	 * @return string
	 */
	public function getDomain($iDomainId)
	{
		$sSql = 'SELECT
				awm_domains.name,
				awm_domains.id_mail_server
			FROM awm_domains
			WHERE awm_domains.id_domain = %d
			GROUP BY awm_domains.id_domain';
		
		return sprintf($sSql, $iDomainId);
	}
	
	/**
	 * Creates SQL-query to delete domain.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function deleteDomain($iDomainId)
	{
		$sSql = 'DELETE FROM awm_domains WHERE id_domain = %d';

		return sprintf($sSql,
			$iDomainId
		);
	}

	public function getDomainMembers($iDomainId)
	{
		$sSql = 'SELECT id_user, email
				FROM awm_accounts
				WHERE id_domain = %d';

		return sprintf($sSql, (int) $iDomainId);
	}

	/**
	 * Creates SQL-query to obtain domain with specified name.
	 * @param string $sDomainName Domain name.
	 * @return string
	 */
	public function getDomainByName($sDomainName)
	{
		$sSql = 'SELECT
				awm_domains.id_domain,
				awm_domains.id_tenant,
				awm_domains.id_mail_server,
				awm_domains.name
			FROM awm_domains
			WHERE awm_domains.name = %s';

		return sprintf($sSql, $this->escapeString($sDomainName));
	}
}

class CommandCreatorMySQL extends CommandCreator
{
}
