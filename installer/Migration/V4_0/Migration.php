<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Installer\Migration\V4_0;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_employee_event'])) {
            $this->getSchemaHelper()->createTable('ohrm_employee_event')
                ->addColumn('event_id', Types::INTEGER, ['Length' => 7, 'Autoincrement' => true])
                ->addColumn('employee_id', Types::INTEGER, ['Length' => 7, 'Notnull' => true, 'Default' => 0])
                ->addColumn('type', Types::STRING, ['Length' => 45, 'Default' => null])
                ->addColumn('event', Types::STRING, ['Length' => 45, 'Default' => null])
                ->addColumn('note', Types::STRING, ['Length' => 150, 'Default' => null])
                ->addColumn('created_date', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('created_by', Types::STRING, ['Length' => 45, 'Default' => null])
                ->setPrimaryKey(['event_id'])
                ->create();
        }

        $adminId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->setParameter('menuTitle', 'Admin')
            ->andWhere('level = :level')
            ->setParameter('level', 1)
            ->executeQuery()
            ->fetchOne();
        $configurationId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->setParameter('menuTitle', 'Configuration')
            ->andWhere('level = :level')
            ->setParameter('level', 2)
            ->andWhere('parent_id = :parentId1')
            ->setParameter('parentId1', $adminId)
            ->executeQuery()
            ->fetchOne();

        $this->createQueryBuilder()
            ->insert('ohrm_screen')
            ->values([
                'name' => ':name',
                'module_id' => ':moduleId',
                'action_url' => ':url',
            ])
            ->setParameter('name', 'Register OAuth Client')
            ->setParameter('moduleId', $this->getDataGroupHelper()->getModuleIdByName('admin'))
            ->setParameter('url', 'registerOAuthClient')
            ->executeQuery();
        $clientScreenId = $this->getConnection()->createQueryBuilder()
            ->select('screen.id')
            ->from('ohrm_screen', 'screen')
            ->where('screen.name = :screenName')
            ->setParameter('screenName', 'Register OAuth Client')
            ->executeQuery()
            ->fetchOne();

        $maxOrder = $this->createQueryBuilder()
            ->select('menu_item.order_hint')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.parent_id = :parentId')
            ->setParameter('parentId', $configurationId)
            ->orderBy('menu_item.order_hint', 'DESC')
            ->executeQuery()
            ->fetchOne();

        $this->createQueryBuilder()
            ->insert('ohrm_menu_item')
            ->values(
                [
                    'menu_title' => ':menuTitle',
                    'screen_id' => ':screenId',
                    'parent_id' => ':ParentId',
                    'level' => ':level',
                    'order_hint' => ':orderHint',
                    'url_extras' => ':urlExtras',
                    'status' => ':status'
                ]
            )
            ->setParameter('menuTitle', 'Register OAuth Client')
            ->setParameter('screenId', $clientScreenId)
            ->setParameter('ParentId', $configurationId)
            ->setParameter('level', 3)
            ->setParameter('orderHint', $maxOrder + 100)
            ->setParameter('urlExtras', null)
            ->setParameter('status', 1)
            ->executeQuery();
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '4.0';
    }
}
