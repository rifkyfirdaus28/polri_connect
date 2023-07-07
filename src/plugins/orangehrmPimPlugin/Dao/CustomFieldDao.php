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

namespace OrangeHRM\Pim\Dao;

use Exception;
use InvalidArgumentException;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\CustomField;
use OrangeHRM\Entity\DisplayField;
use OrangeHRM\Entity\DisplayFieldGroup;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\ReportGroup;
use OrangeHRM\ORM\Exception\TransactionException;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Pim\Dto\CustomFieldSearchFilterParams;

class CustomFieldDao extends BaseDao
{
    /**
     * @param CustomField $customField
     * @return CustomField
     * @throws TransactionException
     */
    public function saveCustomField(CustomField $customField): CustomField
    {
        if ($customField->getFieldNum() === 0) {
            $q = $this->createQueryBuilder(CustomField::class, 'cf');
            $q->select(['cf.fieldNum'])
                ->orderBy('cf.fieldNum');
            $fieldNumbers = $q->getQuery()->execute();

            $i = 1;
            foreach ($fieldNumbers as $num) {
                if ($num['fieldNum'] > $i) {
                    $freeNum = $i;
                    break;
                }
                $i++;

                if ($i > 10) {
                    break;
                }
            }

            if (empty($freeNum) && ($i <= 10)) {
                $freeNum = $i;
            }
            $customField->setFieldNum($freeNum);
        }
        $seqNo = intval($customField->getFieldNum());
        if (!(strlen((string)$seqNo) <= 10 && $seqNo > 0)) {
            throw new InvalidArgumentException('Invalid `seqNo`');
        }
        $this->beginTransaction();
        try {
            $this->persist($customField);
            $this->addCustomFieldToDisplayField($customField);
            $this->commitTransaction();
            return $customField;
        } catch (Exception $exception) {
            $this->rollBackTransaction();
            throw new TransactionException($exception);
        }
    }

    /**
     * @param CustomField $customField
     * @return void
     */
    private function addCustomFieldToDisplayField(CustomField $customField): void
    {
        $customFieldName = 'Custom Fields';
        $customFieldDisplayFieldGroup = $this->getRepository(DisplayFieldGroup::class)
            ->findOneBy(['name' => $customFieldName]);
        $displayFieldName = 'hs_hr_employee.custom' . $customField->getFieldNum();
        $q = $this->createQueryBuilder(DisplayField::class, 'displayField');
        $q->andWhere('displayField.name = :name')->setParameter('name', $displayFieldName);
        $displayField = $this->getRepository(DisplayField::class)
            ->findOneBy(['name' => $displayFieldName, 'displayFieldGroup' => $customFieldDisplayFieldGroup]);
        $reportGroup = $this->getRepository(ReportGroup::class)->findOneBy(['name' => 'pim']);
        if (!$displayField instanceof DisplayField) {
            $displayField = new DisplayField();
            $displayField->setName($displayFieldName);
            $displayField->setReportGroup($reportGroup);
            $displayField->setDisplayFieldGroup($customFieldDisplayFieldGroup);
            $displayField->setFieldAlias('customField' . $customField->getFieldNum());
            $displayField->setElementType('label');
            $displayField->setElementProperty(
                '<xml><getter>customField' . $customField->getFieldNum() . '</getter></xml>'
            );
            $displayField->setWidth(200);
            $displayField->setSortable(false);
            $displayField->setExportable(true);
            $displayField->setDefaultValue('---');
            $displayField->setClassName('OrangeHRM\Core\Report\DisplayField\GenericBasicDisplayField');
        }
        $displayField->setLabel($customField->getName());

        $this->persist($displayField);
    }

    /**
     * @param int $id
     * @return CustomField|null
     * @throws DaoException
     */
    public function getCustomFieldById(int $id): ?CustomField
    {
        try {
            $customField = $this->getRepository(CustomField::class)->findOneBy(
                [
                    'fieldNum' => $id,
                ]
            );
            if ($customField instanceof CustomField) {
                return $customField;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array $toDeleteIds
     * @return int
     * @throws TransactionException
     */
    public function deleteCustomFields(array $toDeleteIds): int
    {
        $this->beginTransaction();
        try {
            $this->deleteDisplayFieldsOfCustomFields($toDeleteIds);
            $q = $this->createQueryBuilder(CustomField::class, 'cf');
            $q->delete()->andWhere($q->expr()->in('cf.fieldNum', ':ids'))->setParameter('ids', $toDeleteIds);
            $this->commitTransaction();
            return $q->getQuery()->execute();
        } catch (Exception $exception) {
            $this->rollBackTransaction();
            throw new TransactionException($exception);
        }
    }

    /**
     * @param array $toDeleteCustomFieldIds
     * @return void
     */
    private function deleteDisplayFieldsOfCustomFields(array $toDeleteCustomFieldIds): void
    {
        if (empty($toDeleteCustomFieldIds)) {
            return;
        }
        $displayFieldNames = [];
        foreach ($toDeleteCustomFieldIds as $toDeleteCustomFieldId) {
            $customField = $this->getCustomFieldById($toDeleteCustomFieldId);
            $displayFieldNames[] = 'hs_hr_employee.custom' . $customField->getFieldNum();
        }
        $q = $this->createQueryBuilder(DisplayField::class, 'displayField');
        $q->delete()
            ->andWhere($q->expr()->in('displayField.name', ':displayFieldNames'))
            ->setParameter('displayFieldNames', $displayFieldNames);
        $q->getQuery()->execute();
    }

    /**
     * Search CustomField
     *
     * @param CustomFieldSearchFilterParams $customFieldSearchParams
     * @return CustomField[]
     * @throws DaoException
     */
    public function searchCustomField(CustomFieldSearchFilterParams $customFieldSearchParams): array
    {
        try {
            $paginator = $this->getSearchCustomFieldPaginator($customFieldSearchParams);
            return $paginator->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param CustomFieldSearchFilterParams $customFieldSearchParams
     * @return Paginator
     */
    private function getSearchCustomFieldPaginator(
        CustomFieldSearchFilterParams $customFieldSearchParams
    ): Paginator {
        $q = $this->createQueryBuilder(CustomField::class, 'cf');
        $this->setSortingAndPaginationParams($q, $customFieldSearchParams);

        if (!empty($customFieldSearchParams->getScreen())) {
            $q->andWhere('cf.screen = :screen')
                ->setParameter('screen', $customFieldSearchParams->getScreen());
        }

        if (!empty($customFieldSearchParams->getFieldNumbers())) {
            $q->andWhere($q->expr()->in('cf.fieldNum', ':fieldNumbers'))
                ->setParameter('fieldNumbers', $customFieldSearchParams->getFieldNumbers());
        }

        return $this->getPaginator($q);
    }

    /**
     * Get Count of Search Query
     *
     * @param CustomFieldSearchFilterParams $customFieldSearchParams
     * @return int
     * @throws DaoException
     */
    public function getSearchCustomFieldsCount(
        CustomFieldSearchFilterParams $customFieldSearchParams
    ): int {
        try {
            $paginator = $this->getSearchCustomFieldPaginator($customFieldSearchParams);
            return $paginator->count();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int $fieldId
     * @return bool
     * @throws DaoException
     */
    public function isCustomFieldInUse(int $fieldId): bool
    {
        try {
            if (0 >= $fieldId || $fieldId > 10) {
                return false;
            }
            $q = $this->createQueryBuilder(Employee::class, 'e');
            $q->where($q->expr()->isNotNull("e.custom{$fieldId}"));
            return count($q->getQuery()->execute()) > 0;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int $fieldId
     * @param string $dropDownValue
     * @return int
     * @throws DaoException
     */
    public function updateEmployeesIfDropDownValueInUse(int $fieldId, string $dropDownValue): int
    {
        try {
            if (0 >= $fieldId || $fieldId > 10) {
                return 0;
            }
            $q = $this->createQueryBuilder(Employee::class, 'e');
            $q->update()
                ->set("e. custom{$fieldId}", ':customField')
                ->where("e.custom{$fieldId} = :dropDownValue")
                ->setParameter('customField', null)
                ->setParameter('dropDownValue', $dropDownValue);
            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
