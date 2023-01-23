<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains\Models;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 * 
 * @property int $TenantId Identifier of tenant which contains the domain.
 * @property int $MailServerId Identifier of mail server which contains the domain.
 * @property string $Name Name of the domain.
 *
 * @package MailDomains
 * @subpackage Classes
 */
class Domain extends \Aurora\System\Classes\Model
{
    protected $table = 'mail_domains';

	protected $moduleName = 'MailDomains';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'TenantId',
        'MailServerId',
        'Name',
        'Count',
        'Properties'
    ];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
    ];

    protected $casts = [
        'Properties' => 'array',
    ];

    protected $attributes = [
    ];

	public function toResponseArray()
	{
		$aRes = [
			'Id' => $this->Id,
			'TenantId' => $this->TenantId,
			'MailServerId' => $this->MailServerId,
			'Name' => $this->Name,
			'Count' => $this->Count,
		];

        $aArgs = ['Domain' => $this];
		\Aurora\System\EventEmitter::getInstance()->emit(
			$this->moduleName, 
            'Domain::ToResponseArray',
			$aArgs,
			$aRes
		);

        return $aRes;
	}
}
