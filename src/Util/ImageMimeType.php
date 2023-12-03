<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

class ImageMimeType
{
    public const IMAGE_MIMETYPE_LIST = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'webp' => 'image/webp',
        'wbmp' => 'image/vnd.wap.wbmp',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'jxr' => 'image/vnd.ms-photo',
        'wdp' => 'image/vnd.ms-photo',
        'hdp' => 'image/vnd.ms-photo',
        'heif' => 'image/heif',
        'heic' => 'image/heic',
        'avif' => 'image/avif',
        'apng' => 'image/apng',
        'flif' => 'image/flif',
        'dib' => 'image/bmp',
        'jp2' => 'image/jp2',
        'jpf' => 'image/jpx',
        'jpx' => 'image/jpx',
        'jpm' => 'image/jpm',
        'mj2' => 'image/mj2',
    ];
}
