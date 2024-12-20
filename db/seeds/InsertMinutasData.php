<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class InsertMinutasData extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = [
            [
                'orgName' => 'Organización Alpha',
                'meetingTitle' => 'Reunión Anual',
                'meetingDate' => '2023-01-15',
                'meetingTime' => '10:00:00',
                'meetingPlace' => 'Sala A',
                'facilitator' => 'Juan Pérez',
                'secretary' => 'María Gómez',
                'attendees' => 'Ana, Luis, Pedro',
                'absentees' => 'Marta, José',
                'guests' => 'Carlos, Beatriz',
                'agenda' => "1. Introducción\n2. Reporte Anual\n3. Planificación",
                'discussion' => 'Se discutieron los logros del año pasado y las metas para el próximo año.',
                'newTopics' => 'Implementación de nuevas tecnologías.',
                'nextMeeting' => '2023-02-15',
                'closingTime' => '12:00:00',
                'closingRemarks' => 'Gracias a todos por su participación.'
            ],
            [
                'orgName' => 'Organización Beta',
                'meetingTitle' => 'Reunión Mensual',
                'meetingDate' => '2023-02-10',
                'meetingTime' => '14:00:00',
                'meetingPlace' => 'Sala B',
                'facilitator' => 'Carlos Ruiz',
                'secretary' => 'Andrea Ramírez',
                'attendees' => 'Mónica, Javier, Laura',
                'absentees' => 'Eduardo, Silvia',
                'guests' => 'Fernando, Patricia',
                'agenda' => "1. Informe Financiero\n2. Proyectos en curso\n3. Nuevas propuestas",
                'discussion' => 'Se revisaron los estados financieros y los avances de los proyectos en curso.',
                'newTopics' => 'Propuesta de nuevos proyectos.',
                'nextMeeting' => '2023-03-10',
                'closingTime' => '16:00:00',
                'closingRemarks' => 'Esperamos sus propuestas para la próxima reunión.'
            ],
            // ... Agrega las demás filas siguiendo el mismo formato
        ];

        $this->table('minutas')->insert($data)->save();
    }
}
