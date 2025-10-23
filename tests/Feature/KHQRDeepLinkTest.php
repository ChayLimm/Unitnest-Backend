<?php

namespace Tests\Unit;

use Tests\TestCase;
use KHQR\BakongKHQR;
use KHQR\Models\SourceInfo;

class KHQRDeepLinkTest extends TestCase
{
    public function test_generate_deep_link_successfully()
    {
        // Arrange
        $sourceInfo = new SourceInfo(
            appIconUrl: 'https://bakong.nbc.gov.kh/images/logo.svg',
            appName: 'Bakong',
            appDeepLinkCallback: 'https://bakong.nbc.gov.kh'
        );

        $payload = "00020101021229220018chaylim_cheng@aclb520459995303840540115802KH5914CHAY LIM Cheng6010PHNOM PENH9917001317597243786616304C450";

        // Act
        $result = BakongKHQR::generateDeepLink($payload, $sourceInfo);

        // Assert
        $this->assertIsString($result, 'The generated deep link should be a string.');
        $this->assertNotEmpty($result, 'The generated deep link should not be empty.');
        $this->assertStringContainsString('bakong.nbc.gov.kh', $result, 'Expected domain missing from deep link.');
        $this->assertStringStartsWith('https://', $result, 'Deep link should start with HTTPS.');
    }
}
