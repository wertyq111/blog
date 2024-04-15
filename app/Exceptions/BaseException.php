<?php
namespace App\Exceptions;

use Exception;

class BaseException extends Exception
{
    const HTTP_OK = 200;

    protected $data;

    protected $code;

    public function __construct($data, int $code = self::HTTP_OK, array $meta = [])
    {
        // 第一个参数是data，是因为想兼容string和array两种数据结构
        // 第二个参数默认取200，是因为公司前端框架限制，所以是status取200，错误码用code表示
        // 如果第二个参数是任意httpStatus（如200，201，204，301，422，500），就只返回httpStatus，如果是自定义错误编码，（如600101，600102），就返回httpstatus为200，返回体中包含message和code。
        // 第三个参数默认为空数组，如果在message和code之外，还需要返回数组，就传递第三个参数
        $this->data = $data;
        $this->code = $code;
        $this->meta = $meta;
//        parent::__construct($data, $code);
    }

    public function render()
    {
        $httpStatus = getHttpStatus();
        $status  = in_array($this->code, $httpStatus) ? $this->code : self::HTTP_OK;
        $content = [];
        if (is_array($this->data)) {
            $content = $this->data;
        }
        if (is_string($this->data)) {
            $content = in_array($this->code, $httpStatus)
                ? [
                    'message' => $this->data
                ]
                : [
                    'message' => $this->data,
                    'code'    => $this->code,
                    //                    'timestamp' => time()
                ];
        }

//        if ($this->meta) {
//            $content = array_add($content, 'meta', $this->meta);
//        }

        return response($content, self::HTTP_OK);
    }
}
