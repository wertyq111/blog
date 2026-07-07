<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class After
{
    /**
     * 记录响应日志,处理成功返回自定义格式
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // 执行动作
        if ($response instanceof JsonResponse) {
            $oriData = $response->getData();
            $isSuccess = in_array($response->getStatusCode(), [200, 201]);
            $errorMessage = $oriData->message ?? '操作失败';

            if (! $isSuccess && isset($oriData->errors)) {
                $errors = (array) $oriData->errors;
                $firstMessages = reset($errors);
                if (is_array($firstMessages) && isset($firstMessages[0])) {
                    $errorMessage = $firstMessages[0];
                }
            }

            $message = [
                'code' => $isSuccess ? 0 : $response->getStatusCode(),
                'msg' => $isSuccess ? '操作成功' : $errorMessage,
            ];

            $data['data'] = $oriData->data ?? $oriData ?? [];

            $data['count'] = $oriData->meta->total ?? 0;
//            if ($oriData->current_page ?? '') {
//                $data['meta'] = [
//                    'total' => $oriData->total ?? 0,
//                    'per_page' => (int)$oriData->per_page ?? 0,
//                    'current_page' => $oriData->current_page ?? 0,
//                    'last_page' => $oriData->last_page ?? 0
//                ];
//            }
//
//            if ($oriData->meta ?? '') {
//                $data['meta'] = [
//                    'total' => $oriData->meta->total ?? 0,
//                    'per_page' => (int)$oriData->meta->per_page ?? 0,
//                    'current_page' => $oriData->meta->current_page ?? 0,
//                    'last_page' => $oriData->meta->last_page ?? 0
//                ];
//            }

            $temp = ($oriData) ? array_merge($message, $data) : $message;

            $response = $response->setData($temp)->setStatusCode(200);
        }

        return $response;
    }
}
