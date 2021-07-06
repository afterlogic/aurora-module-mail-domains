<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @property int $TenantId Identifier of tenant which contains the domain.
 * @property int $MailServerId Identifier of mail server which contains the domain.
 * @property string $Name Name of the domain.
 *
 * @package MailDomains
 * @subpackage Classes
 */
class Domain extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'TenantId'		=> array('int', 0, true),
		'MailServerId'	=> array('int', 0, true),
		'Name'			=> array('string', '', true),
		'Count'			=> array('int', 0, false),
	);
	
	public function toResponseArray()
	{
		return [
			'Id' => $this->Id,
			'TenantId' => $this->TenantId,
			'MailServerId' => $this->MailServerId,
			'Name' => $this->Name,
			'Count' => $this->Count,
		];
	}
}
