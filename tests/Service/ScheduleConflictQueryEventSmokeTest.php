<?php

declare(strict_types=1);

if (!class_exists(\OCP\EventDispatcher\Event::class)) eval('namespace OCP\\EventDispatcher; class Event { public function __construct() {} }');
require_once __DIR__ . '/../../lib/Calendar/ScheduleConflict.php';
require_once __DIR__ . '/../../lib/Calendar/ScheduleConflictQueryEvent.php';

use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
$start = new DateTimeImmutable('2026-07-13T00:00:00Z'); $event = new ScheduleConflictQueryEvent('alice',$start,$start->modify('+1 day'));
$event->add(new ScheduleConflict('shift',$start->modify('+8 hours'),$start->modify('+16 hours'),'Dienst'));
if (count($event->conflicts()) !== 1 || $event->conflicts()[0]->toArray()['type'] !== 'shift') throw new RuntimeException('Konfliktvertrag verletzt.');
echo "ScheduleConflictQueryEventSmokeTest: OK\n";
