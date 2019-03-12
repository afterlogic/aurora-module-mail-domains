<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $TenantId Identifier of tenant which contains the domain.
 * @property int $MailServerId Identifier of mail server which contains the domain.
 * @property string $Name Name of the domain.
 *
 * @package MailDomains
 * @subpackage Classes
 */

namespace Aurora\Modules\MailDomains\Classes;

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
			'Id' => $this->EntityId,
			'TenantId' => $this->TenantId,
			'MailServerId' => $this->MailServerId,
			'Name' => $this->Name,
			'Count' => $this->Count,
		];
	}
}
