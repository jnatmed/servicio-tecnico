<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMinutasTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('minutas');
        $table->addColumn('orgName', 'string', ['limit' => 255])
              ->addColumn('meetingTitle', 'string', ['limit' => 255])
              ->addColumn('meetingDate', 'date')
              ->addColumn('meetingTime', 'time')
              ->addColumn('meetingPlace', 'string', ['limit' => 255])
              ->addColumn('facilitator', 'string', ['limit' => 255])
              ->addColumn('secretary', 'string', ['limit' => 255])
              ->addColumn('attendees', 'text')
              ->addColumn('absentees', 'text')
              ->addColumn('guests', 'text')
              ->addColumn('agenda', 'text')
              ->addColumn('discussion', 'text')
              ->addColumn('newTopics', 'text')
              ->addColumn('nextMeeting', 'text')
              ->addColumn('closingTime', 'time')
              ->addColumn('closingRemarks', 'text')
              ->addColumn('documentPath', 'text')
              ->create();        
    }
}
