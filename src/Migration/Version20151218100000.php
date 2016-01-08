<?php
/**
 * Appiaries Push Notifications EC-CUBE 3 Plugin v1.0.0
 * melissa always loves you!
 * Copyright (c) 2015 Appiaries Co.
 * Under the terms of the MIT license.
 * http://www.appiaries.com/
 *
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 
 */
namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20151218100000 extends AbstractMigration
{

    const DEVICES_TABLE_NAME = 'appiaries_devices';
    const SETTINGS_TABLE_NAME = 'appiaries_settings';

    /** @public */
    public function up(Schema $schema) {
        $this->create_tables($schema);
    }

    /** @public */
    public function down(Schema $schema) {
        $schema->dropTable(self::DEVICES_TABLE_NAME);
        $schema->dropTable(self::SETTINGS_TABLE_NAME);
    }

    /** @protected */
    protected function create_tables(Schema $schema) {
        $devices = $schema->createTable(self::DEVICES_TABLE_NAME);
        $devices->addColumn('id', 'integer', array('autoincrement' => true, 'notnull' => true));
        $devices->addColumn('customer_id', 'integer', array('notnull' => true));
        $devices->addColumn('os', 'smallint', array('notnull' => true, 'unsigned' => true, 'default' => 0));
        $devices->addColumn('device_id', 'string', array('length' => 255, 'notnull' => true));
        $devices->addColumn('attr', 'text', array('notnull' => false));
        $devices->addColumn('created', 'datetime', array('notnull' => true));
        $devices->addColumn('updated', 'datetime', array('notnull' => true));
        $devices->setPrimaryKey(array('id','customer_id'));
        $devices->addIndex(array('id','customer_id'));

        $settings = $schema->createTable(self::SETTINGS_TABLE_NAME);
        $settings->addColumn('id', 'integer', array('notnull' => true));
        $settings->addColumn('datastore_id', 'string', array('length' => 20, 'notnull' => false));
        $settings->addColumn('app_id', 'string', array('length' => 20, 'notnull' => false));
        $settings->addColumn('app_token', 'string', array('length' => 32, 'notnull' => false));
        $settings->addColumn('created', 'datetime', array('notnull' => true));
        $settings->addColumn('updated', 'datetime', array('notnull' => true));
        $settings->setPrimaryKey(array('id'));
    }

}
