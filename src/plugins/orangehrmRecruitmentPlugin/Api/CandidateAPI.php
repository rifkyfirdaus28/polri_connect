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

namespace OrangeHRM\Recruitment\Api;

use DateTime;
use Exception;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Candidate;
use OrangeHRM\Entity\CandidateHistory;
use OrangeHRM\Entity\CandidateVacancy;
use OrangeHRM\Entity\Vacancy;
use OrangeHRM\Entity\WorkflowStateMachine;
use OrangeHRM\ORM\Exception\TransactionException;
use OrangeHRM\Recruitment\Api\Model\CandidateDetailedModel;
use OrangeHRM\Recruitment\Api\Model\CandidateListModel;
use OrangeHRM\Recruitment\Api\Model\CandidateModel;
use OrangeHRM\Recruitment\Dto\CandidateSearchFilterParams;
use OrangeHRM\Recruitment\Service\CandidateService;
use OrangeHRM\Recruitment\Traits\Service\CandidateServiceTrait;
use OrangeHRM\Recruitment\Traits\Service\RecruitmentAttachmentServiceTrait;
use OrangeHRM\Recruitment\Traits\Service\VacancyServiceTrait;

class CandidateAPI extends Endpoint implements CrudEndpoint
{
    use CandidateServiceTrait;
    use VacancyServiceTrait;
    use RecruitmentAttachmentServiceTrait;
    use EntityManagerHelperTrait;
    use DateTimeHelperTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const FILTER_JOB_TITLE_ID = 'jobTitleId';
    public const FILTER_CANDIDATE_ID = 'candidateId';
    public const FILTER_VACANCY_ID = 'vacancyId';
    public const FILTER_HIRING_MANAGER_ID = 'hiringManagerId';
    public const FILTER_STATUS = 'status';
    public const FILTER_KEYWORDS = 'keywords';
    public const FILTER_FROM_DATE = 'fromDate';
    public const FILTER_TO_DATE = 'toDate';
    public const FILTER_METHOD_OF_APPLICATION = 'methodOfApplication';
    public const FILTER_CANDIDATE_NAME = 'candidateName';
    public const FILTER_MODEL = 'model';

    public const PARAMETER_FIRST_NAME = 'firstName';
    public const PARAMETER_MIDDLE_NAME = 'middleName';
    public const PARAMETER_LAST_NAME = 'lastName';
    public const PARAMETER_EMAIL = 'email';
    public const PARAMETER_CONTACT_NUMBER = 'contactNumber';
    public const PARAMETER_VACANCY_ID = 'vacancyId';
    public const PARAMETER_KEYWORDS = 'keywords';
    public const PARAMETER_COMMENT = 'comment';
    public const PARAMETER_DATE_OF_APPLICATION = 'dateOfApplication';
    public const PARAMETER_CONSENT_TO_KEEP_DATA = 'consentToKeepData';

    public const MODEL_DEFAULT = 'default';
    public const MODEL_CANDIDATE_LIST = 'list';
    public const MODEL_CANDIDATE_DETAILED = 'detailed';

    public const MODEL_MAP = [
        self::MODEL_DEFAULT => CandidateModel::class,
        self::MODEL_CANDIDATE_LIST => CandidateListModel::class,
        self::MODEL_CANDIDATE_DETAILED => CandidateDetailedModel::class,
    ];

    public const PARAMETER_RULE_NAME_MAX_LENGTH = 30;
    public const PARAMETER_RULE_KEYWORDS_MAX_LENGTH = 250;
    public const PARAMETER_RULE_COMMENT_MAX_LENGTH = 250;

    /**
     * @inheritDoc
     * @throws BadRequestException
     */
    public function getAll(): EndpointResult
    {
        $candidateSearchFilterParamHolder = new CandidateSearchFilterParams();

        $candidateId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_CANDIDATE_ID
        );

        if (!is_null($candidateId)) {
            $candidateSearchFilterParamHolder->setCandidateIds([$candidateId]);
        } else {
            $accessibleCandidateIds = $this->getUserRoleManager()->getAccessibleEntityIds(Candidate::class);
            $candidateSearchFilterParamHolder->setCandidateIds($accessibleCandidateIds);
        }

        $this->setSortingAndPaginationParams($candidateSearchFilterParamHolder);
        $this->validateDateFilterParams();
        $candidateSearchFilterParamHolder->setJobTitleId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_JOB_TITLE_ID
            )
        );
        $candidateSearchFilterParamHolder->setCandidateId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_CANDIDATE_ID
            )
        );
        $candidateSearchFilterParamHolder->setVacancyId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_VACANCY_ID
            )
        );
        $candidateSearchFilterParamHolder->setHiringManagerId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_HIRING_MANAGER_ID
            )
        );
        $candidateSearchFilterParamHolder->setStatus($this->getStatusFilterParam());
        $candidateSearchFilterParamHolder->setKeywords(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_KEYWORDS
            )
        );
        $candidateSearchFilterParamHolder->setFromDate(
            $this->getFromDateFilterParam()
        );
        $candidateSearchFilterParamHolder->setToDate(
            $this->getToDateFilterParam()
        );
        $candidateSearchFilterParamHolder->setMethodOfApplication(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_METHOD_OF_APPLICATION
            )
        );
        $candidateSearchFilterParamHolder->setCandidateName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_CANDIDATE_NAME
            )
        );
        $candidates = $this->getCandidateService()->getCandidateDao()->getCandidatesList(
            $candidateSearchFilterParamHolder
        );

        $count = $this->getCandidateService()->getCandidateDao()->getCandidatesCount($candidateSearchFilterParamHolder);

        return new EndpointCollectionResult(
            $this->getModelClass(),
            $candidates,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @return string|null
     */
    private function getStatusFilterParam(): ?string
    {
        $state = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_STATUS
        );
        return $state ? CandidateService::STATUS_MAP[$state] : null;
    }

    /**
     * @return DateTime|null
     */
    private function getFromDateFilterParam(): ?DateTime
    {
        return $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_FROM_DATE
        );
    }

    /**
     * @return void
     * @throws BadRequestException
     */
    private function validateDateFilterParams()
    {
        $fromDate = $this->getFromDateFilterParam();
        $toDate = $this->getToDateFilterParam();

        if (($fromDate && $toDate) && $fromDate >= $toDate) {
            throw $this->getBadRequestException('From Date Should Be Earlier Than To Date');
        }
    }

    /**
     * @return DateTime|null
     */
    private function getToDateFilterParam(): ?DateTime
    {
        return $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_TO_DATE
        );
    }

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        $model = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_MODEL,
            self::MODEL_DEFAULT,
        );
        return self::MODEL_MAP[$model];
    }

    /**
     * @return ParamRule
     */
    protected function getModelClassParamRule(): ParamRule
    {
        return $this->getValidationDecorator()->notRequiredParamRule(
            new ParamRule(
                self::FILTER_MODEL,
                new Rule(Rules::IN, [array_keys(self::MODEL_MAP)])
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_CANDIDATE_ID,
                    new Rule(Rules::POSITIVE),
                    new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Candidate::class])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_JOB_TITLE_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_VACANCY_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_HIRING_MANAGER_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_STATUS,
                    new Rule(Rules::IN, [array_keys(CandidateService::STATUS_MAP)])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_KEYWORDS,
                    new Rule(Rules::STRING_TYPE)
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_FROM_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_TO_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_METHOD_OF_APPLICATION,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_CANDIDATE_NAME,
                    new Rule(Rules::STRING_TYPE)
                ),
                true
            ),
            $this->getModelClassParamRule(),
            ...$this->getSortingAndPaginationParamsRules(CandidateSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     * @throws TransactionException
     */
    public function create(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $candidate = new Candidate();
            $this->setCandidate($candidate);
            $candidate = $this->getCandidateService()->getCandidateDao()->saveCandidate($candidate);
            $lastInsertedCandidateId = $candidate->getId();

            $candidateHistory = new CandidateHistory();
            $this->setCommonCandidateHistoryAttributes(
                $candidateHistory,
                $lastInsertedCandidateId,
                CandidateService::RECRUITMENT_CANDIDATE_ACTION_ADD
            );
            $this->getCandidateService()->getCandidateDao()->saveCandidateHistory($candidateHistory);

            $vacancyId = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_VACANCY_ID
            );

            if (!is_null($vacancyId)) {
                $this->attachVacancy($lastInsertedCandidateId, $vacancyId);
            }

            $this->commitTransaction();
            $candidate = $this->getCandidateService()->getCandidateDao()->getCandidateById($lastInsertedCandidateId);
            return new EndpointResourceResult($this->getModelClass(), $candidate);
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @param Candidate $candidate
     */
    private function setCandidate(Candidate $candidate): void
    {
        $candidate->setFirstName(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_FIRST_NAME
            )
        );
        $candidate->setMiddleName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MIDDLE_NAME
            ),
        );
        $candidate->setLastName(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_LAST_NAME
            )
        );
        $candidate->setEmail(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMAIL
            )
        );
        $candidate->setContactNumber(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CONTACT_NUMBER
            )
        );
        $candidate->setKeywords(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_KEYWORDS
            )
        );
        $candidate->setComment(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_COMMENT
            )
        );
        $applicationDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DATE_OF_APPLICATION,
        );
        $candidate->setDateOfApplication(
            $applicationDate instanceof DateTime ?
                $applicationDate :
                $this->getDateTimeHelper()->getNow()
        );
        $candidate->setModeOfApplication(Candidate::MODE_OF_APPLICATION_MANUAL);
        $candidate->setConsentToKeepData(
            $this->getRequestParams()->getBooleanOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CONSENT_TO_KEEP_DATA,
                false
            )
        );
        $candidate->getDecorator()->setAddedPersonById(
            !is_null($candidate->getAddedPerson()) ? $candidate->getAddedPerson()->getEmpNumber() :
                $this->getAuthUser()->getEmpNumber()
        );
    }

    /**
     * @param CandidateVacancy $candidateVacancy
     * @param int $candidateId
     * @param string $status
     */
    private function setCandidateVacancy(CandidateVacancy $candidateVacancy, int $candidateId, string $status): void
    {
        $vacancyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_VACANCY_ID
        );
        $candidateVacancy->getDecorator()->setCandidateById($candidateId);
        $candidateVacancy->getDecorator()->setVacancyById($vacancyId);
        $candidateVacancy->setStatus($status);
        $candidateVacancy->setAppliedDate($this->getDateTimeHelper()->getNow());
    }

    /**
     * @param CandidateHistory $candidateHistory
     * @param int $candidateId
     * @param int $action
     */
    private function setCommonCandidateHistoryAttributes(
        CandidateHistory $candidateHistory,
        int $candidateId,
        int $action
    ): void {
        $candidateHistory->getDecorator()->setCandidateById($candidateId);
        $candidateHistory->setAction($action);
        $candidateHistory->getDecorator()->setPerformedBy($this->getAuthUser()->getEmpNumber());
        $candidateHistory->setPerformedDate($this->getDateTimeHelper()->getNow());
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection($this->getModelClassParamRule(), ...$this->getCommonBodyValidationRules());
    }

    /**
     * @return array
     */
    private function getCommonBodyValidationRules(): array
    {
        return [
            new ParamRule(
                self::PARAMETER_FIRST_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAMETER_RULE_NAME_MAX_LENGTH])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_MIDDLE_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAMETER_RULE_NAME_MAX_LENGTH])
                ),
                true
            ),
            new ParamRule(
                self::PARAMETER_LAST_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAMETER_RULE_NAME_MAX_LENGTH])
            ),
            new ParamRule(
                self::PARAMETER_EMAIL,
                new Rule(Rules::EMAIL)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_CONTACT_NUMBER,
                    new Rule(Rules::PHONE)
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_VACANCY_ID,
                    new Rule(Rules::ENTITY_ID_EXISTS, [Vacancy::class])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_KEYWORDS,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAMETER_RULE_KEYWORDS_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAMETER_RULE_COMMENT_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DATE_OF_APPLICATION,
                    new Rule(Rules::API_DATE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_CONSENT_TO_KEEP_DATA,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_IDS
        );
        $this->getCandidateService()->getCandidateDao()->deleteCandidates($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::ARRAY_TYPE)
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $candidate = $this->getCandidateService()->getCandidateDao()->getCandidateById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($candidate, Candidate::class);
        return new EndpointResourceResult(CandidateDetailedModel::class, $candidate);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Candidate::class])
            )
        );
    }

    /**
     * @inheritDoc
     * @throws TransactionException
     */
    public function update(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $candidateId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                CommonParams::PARAMETER_ID
            );

            $candidate = $this->getCandidateService()->getCandidateDao()->getCandidateById($candidateId);
            $this->throwRecordNotFoundExceptionIfNotExist($candidate, Candidate::class);
            $this->setCandidate($candidate);
            $this->getCandidateService()->getCandidateDao()->saveCandidate($candidate);

            $candidateVacancy = $this->getCandidateService()
                ->getCandidateDao()
                ->getCandidateVacancyByCandidateId($candidateId);
            $this->performCandidateVacancyUpdateCriteria($candidateId, $candidateVacancy);

            $this->commitTransaction();
            return new EndpointResourceResult($this->getModelClass(), $candidate);
        } catch (RecordNotFoundException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Candidate::class])
            ),
            $this->getModelClassParamRule(),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @param int $candidateId
     * @param CandidateVacancy|null $candidateVacancy
     */
    private function performCandidateVacancyUpdateCriteria(int $candidateId, ?CandidateVacancy $candidateVacancy)
    {
        $vacancyId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_VACANCY_ID
        );
        /**
         * vacancyId is not null
         */
        if (!is_null($vacancyId)) {
            /**
             * already existing candidate vacancy available
             */
            if (!is_null($candidateVacancy)) {
                /**
                 * provided vacancyId is not equal to existing candidateVacancy's vacancyId
                 * in that case, existing candidateVacancy record get deleted and new candidateVacancy initiated
                 */
                if ($vacancyId !== $candidateVacancy->getVacancy()->getId()) {
                    $this->removeVacancy($candidateVacancy);
                } else {
                    return;
                }
            }
            $this->attachVacancy($candidateId, $vacancyId);
        } /**
         * already existing candidateVacancy available but null vacancyId given
         * In this case, the existing candidateVacancy record get deleted and
         * now the candidate has not assigned to a vacancy
         */
        elseif (!is_null($candidateVacancy)) {
            $this->removeVacancy($candidateVacancy);
        }
        //else vacancyId is null and no existing vacancy available - Do nothing
    }

    /**
     * @param int $candidateId
     * @param int $vacancyId
     */
    private function attachVacancy(int $candidateId, int $vacancyId): void
    {
        $candidateVacancy = new CandidateVacancy();
        $this->setCandidateVacancy(
            $candidateVacancy,
            $candidateId,
            CandidateService::STATUS_MAP[WorkflowStateMachine::RECRUITMENT_APPLICATION_ACTION_ATTACH_VACANCY]
        );
        $this->getCandidateService()
            ->getCandidateDao()
            ->saveCandidateVacancy($candidateVacancy);
        $candidateHistory = new CandidateHistory();
        $this->setCommonCandidateHistoryAttributes(
            $candidateHistory,
            $candidateId,
            WorkflowStateMachine::RECRUITMENT_APPLICATION_ACTION_ATTACH_VACANCY
        );
        $candidateHistory->getDecorator()->setVacancyById($vacancyId);
        $candidateHistory->setCandidateVacancyName($candidateVacancy->getVacancy()->getName());
        $this->getCandidateService()->getCandidateDao()->saveCandidateHistory($candidateHistory);
    }

    /**
     * @param CandidateVacancy $candidateVacancy
     */
    private function removeVacancy(CandidateVacancy $candidateVacancy)
    {
        $candidateHistoryRecords = $this->getCandidateService()
            ->getCandidateDao()
            ->getCandidateHistoryByCandidateIdAndVacancyId(
                $candidateVacancy->getCandidate()->getId(),
                $candidateVacancy->getVacancy()->getId()
            );
        foreach ($candidateHistoryRecords as $candidateHistoryRecord) {
            $candidateHistoryRecord->setVacancy(null);
            $this->getCandidateService()->getCandidateDao()->saveCandidateHistory($candidateHistoryRecord);
        }
        $this->getCandidateService()->getCandidateDao()->deleteCandidateVacancy(
            $candidateVacancy->getCandidate()->getId()
        );
        $candidateHistory = new CandidateHistory();
        $this->setCommonCandidateHistoryAttributes(
            $candidateHistory,
            $candidateVacancy->getCandidate()->getId(),
            CandidateService::RECRUITMENT_CANDIDATE_VACANCY_REMOVED
        );
        $candidateHistory->setCandidateVacancyName($candidateVacancy->getVacancy()->getName());
        $this->getCandidateService()->getCandidateDao()->saveCandidateHistory($candidateHistory);
    }
}
