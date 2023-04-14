<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailDomains\Models;

/**
 * Aurora\Modules\MailDomains\Models\Domain
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 * @property int $TenantId Identifier of tenant which contains the domain.
 * @property int $MailServerId Identifier of mail server which contains the domain.
 * @property string $Name Name of the domain.
 * @package MailDomains
 * @subpackage Classes
 * @property integer $Id
 * @property integer $Count
 * @property array|null $Properties
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Domain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Domain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Domain query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\MailDomains\Models\Domain whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereMailServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Domain whereUpdatedAt($value)
 * @mixin \Eloquent
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
