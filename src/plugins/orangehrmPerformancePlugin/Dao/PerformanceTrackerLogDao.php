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

namespace OrangeHRM\Performance\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PerformanceTrackerLog;
use OrangeHRM\ORM\QueryBuilderWrapper;
use OrangeHRM\Performance\Dto\PerformanceTrackerLogSearchFilterParams;

class PerformanceTrackerLogDao extends BaseDao
{
    /**
     * @param int $performanceTrackerLogId
     * @return PerformanceTrackerLog|null
     */
    public function getPerformanceTrackerLogById(int $performanceTrackerLogId): ?PerformanceTrackerLog
    {
        $performanceTrackerLog = $this->getRepository(PerformanceTrackerLog::class)->findOneBy(['id' => $performanceTrackerLogId,'status' => PerformanceTrackerLog::STATUS_NOT_DELETED]);
        if ($performanceTrackerLog instanceof PerformanceTrackerLog) {
            return $performanceTrackerLog;
        }
        return null;
    }

    /**
     * @param PerformanceTrackerLogSearchFilterParams $performanceTrackerLogSearchFilterParams
     * @return PerformanceTrackerLog[]
     */
    public function getPerformanceTrackerLogsByTrackerId(PerformanceTrackerLogSearchFilterParams $performanceTrackerLogSearchFilterParams): array
    {
        $query = $this->getPerformanceTrackerLogQueryBuilder($performanceTrackerLogSearchFilterParams)->getQueryBuilder();
        return $query->getQuery()->execute();
    }

    /**
     * @param PerformanceTrackerLogSearchFilterParams $performanceTrackerLogSearchFilterParams
     * @return QueryBuilderWrapper
     */
    private function getPerformanceTrackerLogQueryBuilder(PerformanceTrackerLogSearchFilterParams $performanceTrackerLogSearchFilterParams): QueryBuilderWrapper
    {
        $q = $this->createQueryBuilder(PerformanceTrackerLog::class, 'ptrLog');
        $q->andWhere('ptrLog.performanceTracker = :trackerId')
            ->setParameter('trackerId', $performanceTrackerLogSearchFilterParams->getTrackerId())
            ->andWhere('ptrLog.status = :notDeletedStatus')
            ->setParameter('notDeletedStatus', PerformanceTrackerLog::STATUS_NOT_DELETED);

        $this->setSortingAndPaginationParams($q, $performanceTrackerLogSearchFilterParams);
        return $this->getQueryBuilderWrapper($q);
    }

    /**
     * @param int $rateType
     * @param int $performanceTrackerId
     * @return int
     */
    public function getPerformanceTrackerLogsRateCount(int $rateType, int $performanceTrackerId): int
    {
        $q = $this->createQueryBuilder(PerformanceTrackerLog::class, 'ptrLog');
        $q->andWhere('ptrLog.performanceTracker = :trackerId')
            ->setParameter('trackerId', $performanceTrackerId)
            ->andWhere('ptrLog.achievement = :ratingId')
            ->setParameter('ratingId', $rateType)
            ->andWhere('ptrLog.status = :notDeletedStatus')
            ->setParameter('notDeletedStatus', PerformanceTrackerLog::STATUS_NOT_DELETED);
        return $this->getPaginator($q)->count();
    }

    /**
     * @param PerformanceTrackerLog $performanceTrackerLog
     * @return PerformanceTrackerLog
     */
    public function savePerformanceTrackerLog(PerformanceTrackerLog $performanceTrackerLog): PerformanceTrackerLog
    {
        $this->persist($performanceTrackerLog);
        return $performanceTrackerLog;
    }

    /**
     * @param array $toDeleteIds
     * @return int
     */
    public function deletePerformanceTrackerLog(array $toDeleteIds): int
    {
        $q = $this->createQueryBuilder(PerformanceTrackerLog::class, 'ptrLog');
        $q->update()
            ->set('ptrLog.status', ':deletedStatus')
            ->setParameter('deletedStatus', PerformanceTrackerLog::STATUS_DELETED)
            ->where($q->expr()->in('ptrLog.id', ':ids'))
            ->setParameter('ids', $toDeleteIds);
        return $q->getQuery()->execute();
    }

    /**
     * @param int $userId
     * @return int[]
     */
    public function getPerformanceTrackerLogIdsByUserId(int $userId): array
    {
        $qb = $this->createQueryBuilder(PerformanceTrackerLog::class, 'performanceTrackerLog');
        $qb->select('performanceTrackerLog.id');
        $qb->andWhere($qb->expr()->eq('performanceTrackerLog.user', ':userId'))
            ->setParameter('userId', $userId);
        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    /**
     * @return int[]
     */
    public function getPerformanceTrackerLogsIdList(): array
    {
        $qb = $this->createQueryBuilder(PerformanceTrackerLog::class, 'performanceTrackerLog');
        $qb->select('performanceTrackerLog.id');
        $qb->leftJoin('performanceTrackerLog.employee', 'employee');
        $qb->andWhere($qb->expr()->isNull('employee.purgedAt'));
        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }
}
