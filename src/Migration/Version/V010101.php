<?php
/**
 *
 */

namespace OmniTools\Migration\Version;

class V010101 extends \OmniTools\Migration\AbstractMigration
{
    protected $description = 'Beispiel Migration.';

    /**
     * Perform upwards migration
     */
    protected function up(): void
    {
        $this->executeSql('SAMPLE SQL QUERY');
    }
}
