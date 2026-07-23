<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlatformScriptRun extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'script_key',
        'ordr_no',
        'appl_id',
        'cntrct_no',
        'cntpr_nme',
        'cust_bank_acct_no',
        'cust_pay_amt',
        'actcpe_bchnw_id',
        'actope_bchnw_nme',
        'raw_text',
        'request_data',
        'output',
        'status',
        'error',
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'script_key' => ['column' => 'script_key'],
        'ordr_no' => ['column' => 'ordr_no'],
        'appl_id' => ['column' => 'appl_id'],
    ];
}
