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
use OrangeHRM\Pim\Dto\EmployeeDependentSearchFilterParams;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\EmpDependent;
use OrangeHRM\ORM\ListSorter;

class EmployeeDependentDao extends BaseDao
{
    /**
     * @param int $empNumber Employee Number
     * @return EmpDependent[] Dependents as array
     * @throws DaoException
     */
    public function getEmployeeDependents(int $empNumber): array
    {
        try {
            $q = $this->createQueryBuilder(EmpDependent::class, 'd');
            $q->andWhere('d.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->addOrderBy('d.name', ListSorter::ASCENDING);

            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int $empNumber
     * @param int $seqNo
     * @return EmpDependent|null
     * @throws DaoException
     */
    public function getEmployeeDependent(int $empNumber, int $seqNo): ?EmpDependent
    {
        try {
            $empDependent = $this->getRepository(EmpDependent::class)->findOneBy(
                ['employee' => $empNumber, 'seqNo' => $seqNo]
            );
            if ($empDependent instanceof EmpDependent) {
                return $empDependent;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param EmpDependent $dependent
     * @return EmpDependent
     */
    public function saveEmployeeDependent(EmpDependent $dependent): EmpDependent
    {
        // increment seqNo if not set explicitly
        if ($dependent->getSeqNo() === '0') {
            $q = $this->createQueryBuilder(EmpDependent::class, 'd');
            $empNumber = $dependent->getEmployee()->getEmpNumber();
            $q->andWhere('d.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->select($q->expr()->max('d.seqNo'));
            $maxSeqNo = $q->getQuery()->getSingleScalarResult();
            $seqNo = 1;
            if (!is_null($maxSeqNo)) {
                $seqNo += intval($maxSeqNo);
            }
            $dependent->setSeqNo($seqNo);
        }
        $seqNo = intval($dependent->getSeqNo());
        if (!($seqNo < 100 && $seqNo > 0)) {
            throw new InvalidArgumentException('Invalid `seqNo`');
        }

        $this->persist($dependent);
        return $dependent;
    }

    /**
     * @param int $empNumber
     * @param int[] $entriesToDelete
     * @return int
     * @throws DaoException
     */
    public function deleteEmployeeDependents(int $empNumber, array $entriesToDelete): int
    {
        try {
            $q = $this->createQueryBuilder(EmpDependent::class, 'd');
            $q->delete();
            $q->andWhere('d.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->andWhere($q->expr()->in('d.seqNo', ':ids'))
                ->setParameter('ids', $entriesToDelete);

            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Search
     *
     * @param EmployeeDependentSearchFilterParams $employeeDependentSearchParams
     * @return array
     * @throws DaoException
     */
    public function searchEmployeeDependent(EmployeeDependentSearchFilterParams $employeeDependentSearchParams): array
    {
        try {
            $paginator = $this->getSearchEmployeeDependentPaginator($employeeDependentSearchParams);
            return $paginator->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param EmployeeDependentSearchFilterParams $employeeDependentSearchParams
     * @return Paginator
     */
    private function getSearchEmployeeDependentPaginator(EmployeeDependentSearchFilterParams $employeeDependentSearchParams): Paginator
    {
        $q = $this->createQueryBuilder(EmpDependent::class, 'd');
        $this->setSortingAndPaginationParams($q, $employeeDependentSearchParams);

        $q->andWhere('d.employee = :empNumber')
            ->setParameter('empNumber', $employeeDependentSearchParams->getEmpNumber());

        if (!empty($employeeDependentSearchParams->getName())) {
            $q->andWhere('d.name = :name');
            $q->setParameter('name', $employeeDependentSearchParams->getName());
        }
        if (!empty($employeeDependentSearchParams->getRelationshipType())) {
            $q->andWhere('d.relationshipType = :relationshipType');
            $q->setParameter('relationshipType', $employeeDependentSearchParams->getRelationshipType());
        }
        return $this->getPaginator($q);
    }

    /**
     * Get Count of Search Query
     *
     * @param EmployeeDependentSearchFilterParams $employeeDependentSearchParams
     * @return int
     * @throws DaoException
     */
    public function getSearchEmployeeDependentsCount(EmployeeDependentSearchFilterParams $employeeDependentSearchParams): int
    {
        try {
            $paginator = $this->getSearchEmployeeDependentPaginator($employeeDependentSearchParams);
            return $paginator->count();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
