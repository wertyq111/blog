<?php

namespace App\Services\Api\Admin\ProductImage;

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Contracts\HostResolver;
use App\Services\Api\Admin\ProductImage\Contracts\ProductImagePlatformAdapter;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

class SafeRemoteFetcher
{
    private const IMAGE_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * 初始化安全远程请求服务。
     *
     * @param Factory $http
     * @param HostResolver $hostResolver
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(
        private readonly Factory $http,
        private readonly HostResolver $hostResolver,
    ) {
    }

    /**
     * 安全获取商品页 HTML。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param string $url
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function fetchPage(ProductImagePlatformAdapter $adapter, string $url): string
    {
        [$response] = $this->sendWithRedirects($adapter, 'page', 'GET', $url);
        $contentType = $this->normalizedContentType($response);
        if ($contentType !== 'text/html') {
            $this->closeResponse($response);
            throw new ProductImageExtractorException('商品页返回的内容类型不是 HTML', 502);
        }

        $limit = (int) config('product-image-extractor.limits.page_bytes');
        $contentLength = $this->contentLength($response);
        if ($contentLength !== null && $contentLength > $limit) {
            $this->closeResponse($response);
            throw new ProductImageExtractorException('远程页面大小超过限制', 413);
        }

        return $this->readBody($response, $limit);
    }

    /**
     * 安全读取图片响应头元数据。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param string $url
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function probeImage(ProductImagePlatformAdapter $adapter, string $url): array
    {
        [$response] = $this->sendWithRedirects($adapter, 'image', 'HEAD', $url);
        $contentType = $this->assertImageContentType($response);
        $contentLength = $this->contentLength($response);
        $limit = (int) config('product-image-extractor.limits.image_bytes');

        if ($contentLength !== null && $contentLength > $limit) {
            $this->closeResponse($response);
            throw new ProductImageExtractorException('图片大小超过限制', 413);
        }

        $this->closeResponse($response);

        return [
            'mimeType' => $contentType,
            'extension' => self::IMAGE_MIME_TYPES[$contentType],
            'bytes' => $contentLength,
        ];
    }

    /**
     * 读取首个真实存在的图片候选地址。
     *
     * 只有明确的 404 才尝试下一个候选；连接失败、类型错误和大小超限会立即暴露。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param array $urls
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function probeFirstAvailableImage(ProductImagePlatformAdapter $adapter, array $urls): array
    {
        if (empty($urls)) {
            throw new \LogicException('商品图片候选地址不能为空');
        }

        foreach (array_values(array_unique($urls)) as $url) {
            try {
                return array_merge(['url' => $url], $this->probeImage($adapter, $url));
            } catch (ProductImageExtractorException $exception) {
                if ($exception->remoteStatus() !== 404) {
                    throw $exception;
                }
            }
        }

        throw new ProductImageExtractorException('商品图片原图和可用尺寸版本均不存在', 502);
    }

    /**
     * 安全下载图片到指定文件。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param string $url
     * @param string $destination
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function downloadImage(ProductImagePlatformAdapter $adapter, string $url, string $destination): array
    {
        [$response] = $this->sendWithRedirects($adapter, 'image', 'GET', $url);
        $headerContentType = $this->assertImageContentType($response);
        $contentLength = $this->contentLength($response);
        $limit = (int) config('product-image-extractor.limits.image_bytes');

        if ($contentLength !== null && $contentLength > $limit) {
            $this->closeResponse($response);
            throw new ProductImageExtractorException('图片大小超过限制', 413);
        }

        $bytes = $this->writeBody($response, $destination, $limit);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualContentType = $finfo->file($destination);
        if (!is_string($actualContentType)
            || !isset(self::IMAGE_MIME_TYPES[$actualContentType])
            || $actualContentType !== $headerContentType) {
            throw new ProductImageExtractorException('远程文件不是有效的商品图片', 502);
        }

        $size = getimagesize($destination);
        if ($size === false) {
            throw new ProductImageExtractorException('无法读取商品图片尺寸', 502);
        }

        return [
            'mimeType' => $actualContentType,
            'extension' => self::IMAGE_MIME_TYPES[$actualContentType],
            'bytes' => $bytes,
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    /**
     * 逐次校验并跟随远程重定向。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param string $resourceType
     * @param string $method
     * @param string $url
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function sendWithRedirects(
        ProductImagePlatformAdapter $adapter,
        string $resourceType,
        string $method,
        string $url,
    ): array {
        $currentUrl = $url;
        $maxRedirects = (int) config('product-image-extractor.limits.redirects');

        for ($redirects = 0; $redirects <= $maxRedirects; $redirects++) {
            $allowedHosts = $resourceType === 'page' ? $adapter->pageHosts() : $adapter->imageHosts();
            $resourceType === 'page'
                ? $adapter->validatePageUrl($currentUrl)
                : $adapter->validateImageUrl($currentUrl);

            [$host, $port, $ip] = $this->validateAndResolveUrl($currentUrl, $allowedHosts);

            try {
                $response = $this->http
                    ->withHeaders([
                        'Accept' => $resourceType === 'page' ? 'text/html' : 'image/avif,image/webp,image/png,image/jpeg',
                        'User-Agent' => 'Mozilla/5.0 (compatible; BlogProductImageExtractor/1.0)',
                    ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'connect_timeout' => (int) config('product-image-extractor.limits.connect_timeout'),
                        'timeout' => (int) config('product-image-extractor.limits.request_timeout'),
                        'stream' => true,
                        'verify' => true,
                        'curl' => [
                            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $this->curlResolveAddress($ip))],
                        ],
                    ])
                    ->send($method, $currentUrl);
            } catch (ConnectionException) {
                throw new ProductImageExtractorException('远程资源连接失败', 502);
            }

            if ($this->isRedirect($response)) {
                if ($redirects === $maxRedirects) {
                    $this->closeResponse($response);
                    throw new ProductImageExtractorException('远程资源重定向次数过多', 502);
                }

                $location = $response->header('Location');
                $this->closeResponse($response);
                if (!is_string($location) || trim($location) === '') {
                    throw new ProductImageExtractorException('远程资源重定向地址无效', 502);
                }

                $currentUrl = (string) UriResolver::resolve(Utils::uriFor($currentUrl), Utils::uriFor($location));
                continue;
            }

            if (!$response->successful()) {
                $status = $response->status();
                $this->closeResponse($response);
                throw new ProductImageExtractorException("远程资源返回异常状态：{$status}", 502, $status);
            }

            return [$response, $currentUrl];
        }

        throw new ProductImageExtractorException('远程资源请求失败', 502);
    }

    /**
     * 校验远程地址并锁定公网 IP。
     *
     * @param string $url
     * @param array $allowedHosts
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function validateAndResolveUrl(string $url, array $allowedHosts): array
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new ProductImageExtractorException('远程资源地址无效');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($scheme !== 'https'
            || $host === ''
            || preg_match('/^[a-z0-9.-]+$/', $host) !== 1
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || !in_array($host, $allowedHosts, true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
            || $port !== 443) {
            throw new ProductImageExtractorException('远程资源地址不在允许范围内');
        }

        $addresses = $this->hostResolver->resolve($host);
        if (empty($addresses)) {
            throw new ProductImageExtractorException('远程资源域名无法解析', 502);
        }

        foreach ($addresses as $address) {
            if (!$this->isPublicIp($address)) {
                throw new ProductImageExtractorException('远程资源域名解析到非公网地址');
            }
        }

        $ipv4 = array_values(array_filter($addresses, static fn (string $address) => filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false));
        $ip = $ipv4[0] ?? $addresses[0];

        return [$host, $port, $ip];
    }

    /**
     * 判断 IP 是否为公网地址。
     *
     * @param string $address
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function isPublicIp(string $address): bool
    {
        if (str_starts_with(strtolower($address), '::ffff:')) {
            return false;
        }

        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * 读取有大小上限的响应正文。
     *
     * @param Response $response
     * @param int $limit
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function readBody(Response $response, int $limit): string
    {
        $stream = $response->toPsrResponse()->getBody();
        $content = '';

        while (!$stream->eof()) {
            $content .= $stream->read(8192);
            if (strlen($content) > $limit) {
                $stream->close();
                throw new ProductImageExtractorException('远程页面大小超过限制', 413);
            }
        }

        $stream->close();

        return $content;
    }

    /**
     * 将有大小上限的响应正文写入文件。
     *
     * @param Response $response
     * @param string $destination
     * @param int $limit
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function writeBody(Response $response, string $destination, int $limit): int
    {
        $target = fopen($destination, 'wb');
        if ($target === false) {
            $this->closeResponse($response);
            throw new \RuntimeException('无法创建图片临时文件');
        }

        $stream = $response->toPsrResponse()->getBody();
        $bytes = 0;

        try {
            while (!$stream->eof()) {
                $chunk = $stream->read(8192);
                $bytes += strlen($chunk);
                if ($bytes > $limit) {
                    throw new ProductImageExtractorException('图片大小超过限制', 413);
                }
                if ($chunk !== '' && fwrite($target, $chunk) === false) {
                    throw new \RuntimeException('写入图片临时文件失败');
                }
            }
        } finally {
            fclose($target);
            $stream->close();
        }

        return $bytes;
    }

    /**
     * 校验图片响应类型。
     *
     * @param Response $response
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function assertImageContentType(Response $response): string
    {
        $contentType = $this->normalizedContentType($response);
        if (!isset(self::IMAGE_MIME_TYPES[$contentType])) {
            $this->closeResponse($response);
            throw new ProductImageExtractorException('远程资源不是支持的图片类型', 502);
        }

        return $contentType;
    }

    /**
     * 读取标准化响应类型。
     *
     * @param Response $response
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function normalizedContentType(Response $response): string
    {
        return strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
    }

    /**
     * 读取响应内容长度。
     *
     * @param Response $response
     * @return int|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function contentLength(Response $response): ?int
    {
        $value = $response->header('Content-Length');

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    /**
     * 判断响应是否为重定向。
     *
     * @param Response $response
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function isRedirect(Response $response): bool
    {
        return in_array($response->status(), [301, 302, 303, 307, 308], true);
    }

    /**
     * 关闭响应数据流。
     *
     * @param Response $response
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function closeResponse(Response $response): void
    {
        $response->toPsrResponse()->getBody()->close();
    }

    /**
     * 格式化 curl DNS 锁定地址。
     *
     * @param string $ip
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function curlResolveAddress(string $ip): string
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? "[{$ip}]" : $ip;
    }
}
