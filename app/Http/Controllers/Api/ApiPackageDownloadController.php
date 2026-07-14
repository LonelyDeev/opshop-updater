<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackageDownloadToken;
use App\Services\PackageApiAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;

class ApiPackageDownloadController extends Controller
{
    public function __construct(private PackageApiAuthService $authService) {}

    /* ===================================================================
     *  GET /api/v1/packages/download/{token}
     *  دانلود فایل ZIP با token موقت
     * =================================================================== */
    public function download(Request $request, string $dlToken)
    {
        try {
            // احراز هویت (مشتری باید لاگین باشه)
            $customer = $this->authService->authenticate($request);

            $downloadToken = PackageDownloadToken::where('token', $dlToken)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$downloadToken) {
                return response()->json(['error' => 'توکن دانلود نامعتبر است.'], 404);
            }

            if (!$downloadToken->isValid()) {
                return response()->json(['error' => 'توکن دانلود منقضی یا استفاده‌شده است.'], 403);
            }

            $version = $downloadToken->version;
            if (!$version || !Storage::disk('local')->exists($version->file_path)) {
                return response()->json(['error' => 'فایل پکیج یافت نشد.'], 404);
            }

            // علامت‌گذاری توکن به‌عنوان استفاده‌شده
            $downloadToken->markAsUsed($request->ip());

            // افزایش تعداد دانلودها
            $version->incrementDownloads();
            $version->package->incrementDownloads();

            $fileName = basename($version->file_path);
            $mimeType = Storage::disk('local')->mimeType($version->file_path);
            $size = Storage::disk('local')->size($version->file_path);

            return new StreamedResponse(function () use ($version) {
                $stream = Storage::disk('local')->readStream($version->file_path);
                while (!feof($stream)) {
                    echo fread($stream, 1024 * 1024); // 1MB chunks
                }
                fclose($stream);
            }, 200, [
                'Content-Type'        => $mimeType,
                'Content-Length'      => $size,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'X-Package-Version'   => $version->version,
                'X-Package-Hash'      => $version->file_hash,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }
}
