<?php
use App\Models\Room;
use Illuminate\Support\Facades\Log;
Log::info("dfadfa");
$services = Room::find(1)->services;
Log::info($services->toArray());
