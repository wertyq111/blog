<?php

/**
 * 表格列配置
 */
return [
    // 客户信息
    'tobacco-customer' => [
        [
            'prop' => 'code',
            'label' => '编码',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'name',
            'label' => '名称',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'stageName',
            'label' => '客户分类',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
    ],
    // 订货
    'tobacco-order' => [
        [
            'prop' => 'customerName',
            'label' => '客户名称',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'requireNumber',
            'label' => '要货数量',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'orderNumber',
            'label' => '订货数量',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ]
    ],
    // 1024定点供货
    'tobacco-designated' => [
        [
            'prop' => 'customerName',
            'label' => '客户名称',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'number',
            'label' => '数量',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ]
    ],
    // 补供供货
    'tobacco-supplement' => [
        [
            'prop' => 'customerName',
            'label' => '客户名称',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'number1',
            'label' => '贵烟(硬黄精品)补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number2',
            'label' => '云烟（云龙）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number3',
            'label' => '长白山（777）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number4',
            'label' => '双喜（软经典）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number5',
            'label' => '黄金叶（乐途）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number6',
            'label' => '芙蓉王（硬）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number7',
            'label' => '泰山（硬红八喜)补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number8',
            'label' => '好猫（细支长乐）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number9',
            'label' => '好猫（金丝猴）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number10',
            'label' => '利群（长嘴）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'number11',
            'label' => '利群（软红长嘴）补供数',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
        [
            'prop' => 'totalNumber',
            'label' => '合计数量',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 80
        ],
    ],
    // 云烟补供
    'tobacco-yun' => [
        [
            'prop' => 'customerName',
            'label' => '客户名称',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ],
        [
            'prop' => 'number',
            'label' => '数量',
            'align' => 'center',
            'showOverflowTooltip' => true,
            'minWidth' => 100
        ]
    ],
    // 供货限量
    'tobacco-supply' => [
        'assemble' => [
            [
                [
                    'prop' => 'code',
                    'label' => '编码',
                    'align' => 'center',
                    'showOverflowTooltip' => true,
                    'minWidth' => 80
                ],
                [
                    'prop' => 'name',
                    'label' => '名称',
                    'align' => 'center',
                    'showOverflowTooltip' => true,
                    'minWidth' => 100
                ]
            ],
            'custom',
            [
                [
                    'prop' => 'remark',
                    'label' => '备注',
                    'align' => 'center',
                    'showOverflowTooltip' => true,
                    'minWidth' => 100
                ]
            ]
        ]
    ],
];
