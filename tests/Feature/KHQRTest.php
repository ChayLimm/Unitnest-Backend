<?php

namespace Tests\Unit;

use Tests\TestCase;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class KHQRTest extends TestCase
{
    public function test_generate_qr_from_individual_info()
    {
        // Arrange: create individual info
        $individualInfo = new IndividualInfo(
            bakongAccountID: config('bakong.merchant_id'),
            merchantName: config('bakong.merchant_name'),
            merchantCity: config('bakong.merchant_city'),
            currency: KHQRData::CURRENCY_USD,
            amount: 1
        );

        // Act: generate the QR (this assumes KHQRData or KHQRGenerator class exists)
        $qrString = BakongKHQR::generateIndividual($individualInfo);

        // Assert: check that QR string is not empty
        $this->assertNotEmpty($qrString);
        $this->assertStringContainsString(config('bakong.merchant_id'), $qrString);

        fwrite(STDOUT, "\nGenerated KHQR:\n" . $qrString . "\n");
    }
}
