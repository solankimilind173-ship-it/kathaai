<?php

namespace Tests\Unit\Services;

use App\Services\FfmpegPathResolver;
use App\Services\VideoWatermarkService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class VideoWatermarkServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FfmpegPathResolver::clearCache();
        Process::fake([
            'ffmpeg' => Process::result('ffmpeg version 4.4', '', 0),
        ]);
        $this->service = new VideoWatermarkService;
    }

    public function test_apply_watermark_returns_null_when_logo_file_missing(): void
    {
        Config::set('watermark.logo_path', __DIR__.'/nonexistent-logo.png');
        $result = $this->service->applyWatermark('/tmp/input.mp4');
        $this->assertNull($result);
    }

    public function test_apply_watermark_returns_null_when_ffmpeg_fails(): void
    {
        $logo = public_path('images/kathaai-logo.png');
        if (! is_file($logo)) {
            $this->markTestSkipped('Logo file not present');
        }
        Config::set('watermark.logo_path', $logo);
        Process::fake([
            'ffmpeg' => Process::result('', 'FFmpeg error', 1),
        ]);
        $result = $this->service->applyWatermark('/tmp/input.mp4');
        $this->assertNull($result);
    }

    public function test_temp_output_path_format(): void
    {
        $ref = new \ReflectionClass(VideoWatermarkService::class);
        $method = $ref->getMethod('tempOutputPath');
        $method->setAccessible(true);
        $path = $method->invoke($this->service, '/var/videos/my-video.mp4');
        $this->assertStringContainsString('my-video.watermarked.mp4', $path);
        $this->assertSame('/var/videos', dirname($path));
    }
}
