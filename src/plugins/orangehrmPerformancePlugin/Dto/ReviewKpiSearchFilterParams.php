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

namespace OrangeHRM\Performance\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class ReviewKpiSearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = ['kpi.title'];

    protected ?int $reviewId = null;
    protected string $reviewerGroupName;

    /**
     * @param int $reviewId
     */
    public function __construct()
    {
        $this->setSortField('kpi.title');
    }

    /**
     * @return int
     */
    public function getReviewId(): int
    {
        return $this->reviewId;
    }

    /**
     * @param int $reviewId
     */
    public function setReviewId(int $reviewId): void
    {
        $this->reviewId = $reviewId;
    }

    /**
     * @return string
     */
    public function getReviewerGroupName(): string
    {
        return $this->reviewerGroupName;
    }

    /**
     * @param string $reviewerGroupName
     */
    public function setReviewerGroupName(string $reviewerGroupName): void
    {
        $this->reviewerGroupName = $reviewerGroupName;
    }
}
