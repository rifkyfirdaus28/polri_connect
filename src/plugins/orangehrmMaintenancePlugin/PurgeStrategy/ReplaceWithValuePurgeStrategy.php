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

namespace OrangeHRM\Maintenance\PurgeStrategy;

use OrangeHRM\Maintenance\FormatValueStrategy\ValueFormatter;

class ReplaceWithValuePurgeStrategy extends PurgeStrategy
{
    /**
     * @param int $employeeNumber
     */
    public function purge(int $employeeNumber): void
    {
        $matchByValues = $this->getMatchByValues($employeeNumber);
        $entityClassName = $this->getEntityClassName();
        $purgeEntities = $this->getEntityRecords($matchByValues, $entityClassName);
        foreach ($purgeEntities as $purgeEntity) {
            $this->purgeRecord($purgeEntity);
        }
    }

    /**
     * @param object $purgeEntity
     */
    public function purgeRecord(object $purgeEntity): void
    {
        $replaceFields = $this->getParameters();
        foreach ($replaceFields as $replaceColumnArrayData) {
            $currentField = $replaceColumnArrayData['field'];
            $getCurrentField = $replaceColumnArrayData['getter'] ?? 'get' . ucfirst($currentField);
            $currentFieldValue = $purgeEntity->$getCurrentField();
            $replaceStrategy = $this->getReplaceStrategy($replaceColumnArrayData['class']);
            $replace = $replaceStrategy->getFormattedValue($currentFieldValue);
            $setCurrentField = $replaceColumnArrayData['setter'] ?? 'set' . ucfirst($currentField);
            $purgeEntity->$setCurrentField($replace);
        }
        $this->getEntityManager()->persist($purgeEntity);
    }

    /**
     * @param string $strategy
     * @return ValueFormatter
     */
    protected function getReplaceStrategy(string $strategy): ValueFormatter
    {
        $formatValueStrategy = 'OrangeHRM\\Maintenance\\PurgeStrategy\\FormatValue\\' . $strategy;
        return new $formatValueStrategy();
    }
}
