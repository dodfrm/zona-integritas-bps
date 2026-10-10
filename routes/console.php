<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('zi:send-deadline-reminders')->dailyAt('07:00')->description('Pengingat tenggat rencana aksi');
