<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { if (!class_exists(Event::class)) { class Event { public function __construct() {} } } }

namespace {

use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
$start = new DateTimeImmutable('2026-07-13T00:00:00Z'); $event = new ScheduleConflictQueryEvent('alice',$start,$start->modify('+1 day'));
$event->add(new ScheduleConflict('shift',$start->modify('+8 hours'),$start->modify('+16 hours'),'Dienst'));
if (count($event->conflicts()) !== 1 || $event->conflicts()[0]->toArray()['type'] !== 'shift') throw new RuntimeException('Konfliktvertrag verletzt.');

$scoped = new ScheduleConflictQueryEvent('alice', $start, $start->modify('+1 day'), 'flzplaner');
if ($scoped->contractVersion() !== '1.0') throw new RuntimeException('Der öffentliche Konfliktvertrag besitzt keine stabile Schema-Version.');
$scoped->add(new ScheduleConflict('shift', $start->modify('+8 hours'), $start->modify('+14 hours'), 'Assistenz', 'flzplaner'));
$scoped->add(new ScheduleConflict('shift', $start->modify('+9 hours'), $start->modify('+17 hours'), 'Dienst/Büro', 'flzcalendar'));
if ($scoped->requesterAppId() !== 'flzplaner') throw new RuntimeException('Die anfragende App fehlt im öffentlichen Konfliktvertrag.');
if (count($scoped->conflicts()) !== 1) throw new RuntimeException('Eigenmeldungen der anfragenden App müssen zentral ausgeschlossen werden.');
$external = $scoped->conflicts()[0];
if ($external->sourceAppId() !== 'flzcalendar' || $external->label() !== 'Dienst/Büro') throw new RuntimeException('Provider und sichere Anzeige fehlen am Konflikt.');
if (($external->toArray()['sourceAppId'] ?? null) !== 'flzcalendar') throw new RuntimeException('Die Provider-ID fehlt in der serialisierten API.');

foreach (['INVALID APP', '../flzplaner'] as $invalidAppId) {
    try {
        new ScheduleConflictQueryEvent('alice', $start, $start->modify('+1 day'), $invalidAppId);
        throw new RuntimeException('Ungültige anfragende App-ID wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}
echo "ScheduleConflictQueryEventSmokeTest: OK\n";
}
