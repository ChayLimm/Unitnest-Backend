<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\RoomType;
use App\Models\Service;

class SeederDebugTest extends TestCase
{
    public function test_room_type_creation()
    {
        try {
            $rt = RoomType::create([
                'room_type_name' => 'DebugSingle', 
                'description' => 'DebugDesc'
            ]);
            dump('RoomType created: ' . $rt->id);
            $this->assertTrue($rt->exists);
        } catch (\Exception $e) {
            file_put_contents('debug_error.log', 'RoomType Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            $this->fail('RoomType Error: ' . $e->getMessage());
        }
    }

    public function test_service_creation()
    {
        try {
            $s = Service::create([
                'name' => 'DebugService',
                'unit_price' => 10.5,
                'description' => 'DebugDesc',
                'unit_name' => 'kWh',
                'service_name' => 'Elec'
            ]);
            dump('Service created: ' . $s->id);
            $this->assertTrue($s->exists);
        } catch (\Exception $e) {
            file_put_contents('debug_error.log', 'Service Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            $this->fail('Service Error: ' . $e->getMessage());
        }
    }
}
