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

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Admin\Api\Model\EmploymentStatusModel;
use OrangeHRM\Admin\Dto\EmploymentStatusSearchFilterParams;
use OrangeHRM\Admin\Service\EmploymentStatusService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\EmploymentStatus;

class EmploymentStatusAPI extends Endpoint implements CrudEndpoint
{
    public const PARAMETER_NAME = 'name';

    public const FILTER_NAME = 'name';

    /**
     * @var null|EmploymentStatusService
     */
    protected ?EmploymentStatusService $employmentStatusService = null;

    /**
     * @return EmploymentStatusService
     */
    public function getEmploymentStatusService(): EmploymentStatusService
    {
        if (is_null($this->employmentStatusService)) {
            $this->employmentStatusService = new EmploymentStatusService();
        }
        return $this->employmentStatusService;
    }

    /**
     *@OA\Get(
     *     path="/api/v2/admin/employment-statuses/{id}",
     *     tags={"Admin/Employment Status"},
     * @OA\PathParameter(
     *     name="id",
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *     response="200",
     *     description="Success",
     *     @OA\JsonContent(
     *         @OA\Property(
     *             property="data",
     *             ref="#/components/schemas/Admin-EmploymentStatusModel"
     *         ),
     *         @OA\Property(property="meta", type="object")
     *     )
     * ),
     * @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResourceResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $employmentStatus = $this->getEmploymentStatusService()->getEmploymentStatusById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($employmentStatus);

        return new EndpointResourceResult(EmploymentStatusModel::class, $employmentStatus);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/admin/employment-statuses",
     *     tags={"Admin/Employment Status"},
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=EmploymentStatusSearchFilterParams::ALLOWED_SORT_FIELDS)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Admin-EmploymentStatusModel")
     *             ),
     *             @OA\Property(property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointCollectionResult
    {
        $employmentStatusSearchParams = new EmploymentStatusSearchFilterParams();
        $this->setSortingAndPaginationParams($employmentStatusSearchParams);
        $employmentStatusSearchParams->setName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_NAME
            )
        );
        $employmentStatuses = $this->getEmploymentStatusService()->searchEmploymentStatus(
            $employmentStatusSearchParams
        );
        return new EndpointCollectionResult(
            EmploymentStatusModel::class,
            $employmentStatuses,
            new ParameterBag(
                [
                    CommonParams::PARAMETER_TOTAL => $this->getEmploymentStatusService()
                        ->getSearchEmploymentStatusesCount($employmentStatusSearchParams)
                ]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::FILTER_NAME),
            ...$this->getSortingAndPaginationParamsRules(EmploymentStatusSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/admin/employment-statuses",
     *     tags={"Admin/Employment Status"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             required={"name"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Admin-EmploymentStatusModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function create(): EndpointResourceResult
    {
        $employmentStatus = new EmploymentStatus();
        $employmentStatus = $this->saveEmploymentStatus($employmentStatus);

        return new EndpointResourceResult(EmploymentStatusModel::class, $employmentStatus);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_NAME),
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/admin/employment-statuses/{id}",
     *     tags={"Admin/Employment Status"},
     *     @OA\PathParameter(
     *         name="id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             required={"name"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Admin-EmploymentStatusModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function update(): EndpointResourceResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $employmentStatus = $this->getEmploymentStatusService()->getEmploymentStatusById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($employmentStatus);
        $employmentStatus = $this->saveEmploymentStatus($employmentStatus);

        return new EndpointResourceResult(EmploymentStatusModel::class, $employmentStatus);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(self::PARAMETER_NAME),
        );
    }

    /**
     * @param EmploymentStatus $employmentStatus
     * @return EmploymentStatus
     */
    public function saveEmploymentStatus(EmploymentStatus $employmentStatus): EmploymentStatus
    {
        $name = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);
        $employmentStatus->setName($name);
        return $this->getEmploymentStatusService()->saveEmploymentStatus($employmentStatus);
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/admin/employment-statuses",
     *     tags={"Admin/Employment Status"},
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse")
     * )
     *
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getEmploymentStatusService()->deleteEmploymentStatus($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS),
        );
    }
}
