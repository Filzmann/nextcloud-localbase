<?php

declare(strict_types=1);

if (!class_exists(\OCP\EventDispatcher\Event::class)) eval('namespace OCP\\EventDispatcher; class Event { public function __construct() {} }');

use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
$start = new DateTimeImmutable('2026-07-13T00:00:00Z'); $event = new ScheduleConflictQueryEvent('alice',$start,$start->modify('+1 day'));
$event->add(new ScheduleConflict('shift',$start->modify('+8 hours'),$start->modify('+16 hours'),'Dienst'));
if (count($event->conflicts()) !== 1 || $event->conflicts()[0]->toArray()['type'] !== 'shift') throw new RuntimeException('Konfliktvertrag verletzt.');

$scoped = new ScheduleConflictQueryEvent('alice', $start, $start->modify('+1 day'), 'adplaner');
if ($scoped->contractVersion() !== '1.0') throw new RuntimeException('Der öffentliche Konfliktvertrag besitzt keine stabile Schema-Version.');
$scoped->add(new ScheduleConflict('shift', $start->modify('+8 hours'), $start->modify('+14 hours'), 'Assistenz', 'adplaner'));
$scoped->add(new ScheduleConflict('shift', $start->modify('+9 hours'), $start->modify('+17 hours'), 'Dienst/Büro', 'adcalendar'));
if ($scoped->requesterAppId() !== 'adplaner') throw new RuntimeException('Die anfragende App fehlt im öffentlichen Konfliktvertrag.');
if (count($scoped->conflicts()) !== 1) throw new RuntimeException('Eigenmeldungen der anfragenden App müssen zentral ausgeschlossen werden.');
$external = $scoped->conflicts()[0];
if ($external->sourceAppId() !== 'adcalendar' || $external->label() !== 'Dienst/Büro') throw new RuntimeException('Provider und sichere Anzeige fehlen am Konflikt.');
if (($external->toArray()['sourceAppId'] ?? null) !== 'adcalendar') throw new RuntimeException('Die Provider-ID fehlt in der serialisierten API.');

foreach (['INVALID APP', '../adplaner'] as $invalidAppId) {
    try {
        new ScheduleConflictQueryEvent('alice', $start, $start->modify('+1 day'), $invalidAppId);
        throw new RuntimeException('Ungültige anfragende App-ID wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}
echo "ScheduleConflictQueryEventSmokeTest: OK\n";
