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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\Interview;
use OrangeHRM\Entity\InterviewAttachment;
use OrangeHRM\Recruitment\Api\Model\InterviewAttachmentModel;
use OrangeHRM\Recruitment\Dto\InterviewAttachmentSearchFilterParams;
use OrangeHRM\Recruitment\Dto\RecruitmentAttachment;
use OrangeHRM\Recruitment\Traits\Service\CandidateServiceTrait;
use OrangeHRM\Recruitment\Traits\Service\RecruitmentAttachmentServiceTrait;

class InterviewAttachmentAPI extends Endpoint implements CrudEndpoint
{
    use RecruitmentAttachmentServiceTrait;
    use CandidateServiceTrait;

    public const PARAMETER_COMMENT = 'comment';
    public const PARAMETER_ATTACHMENT_ID = 'attachmentId';
    public const PARAMETER_INTERVIEW_ID = 'interviewId';
    public const PARAMETER_ATTACHMENT = 'attachment';
    public const PARAMETER_CURRENT_ATTACHMENT = 'currentAttachment';

    public const PARAM_RULE_FILE_NAME_MAX_LENGTH = 200;
    public const PARAM_RULE_CURRENT_ATTACHMENT_MAX_LENGTH = 16;
    public const PARAM_RULE_COMMENT_MAX_LENGTH = 255;

    public const INTERVIEW_ATTACHMENT_REPLACE_CURRENT = 'replaceCurrent';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $interviewAttachmentParamHolder = new InterviewAttachmentSearchFilterParams();
        $this->setSortingAndPaginationParams($interviewAttachmentParamHolder);

        $interview = $this->getCandidateService()
            ->getCandidateDao()
            ->getInterviewById($this->getInterviewId());
        $this->throwRecordNotFoundExceptionIfNotExist($interview, Interview::class);

        $interviewAttachmentParamHolder->setInterviewId(
            $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_INTERVIEW_ID
            )
        );
        $attachments = $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->getInterviewAttachments($interviewAttachmentParamHolder);
        $count = $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->getInterviewAttachmentsCount($interviewAttachmentParamHolder);
        return new EndpointCollectionResult(
            InterviewAttachmentModel::class,
            $attachments,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_INTERVIEW_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Interview::class])
            ),
            ...$this->getSortingAndPaginationParamsRules(InterviewAttachmentSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $interview = $this->getCandidateService()
            ->getCandidateDao()
            ->getInterviewById($this->getInterviewId());
        $this->throwRecordNotFoundExceptionIfNotExist($interview, Interview::class);
        $interviewAttachment = new InterviewAttachment();
        $this->setInterviewAttachment($interviewAttachment);
        $this->setBase64Attachment($interviewAttachment);
        $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->saveInterviewAttachment($interviewAttachment);
        return new EndpointResourceResult(
            InterviewAttachmentModel::class,
            $this->getRecruitmentAttachment($interviewAttachment)
        );
    }

    /**
     * @param InterviewAttachment $interviewAttachment
     */
    private function setInterviewAttachment(InterviewAttachment $interviewAttachment)
    {
        $interviewAttachment->getDecorator()->setInterviewById(
            $this->getInterviewId()
        );
        $interviewAttachment->setComment(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_COMMENT
            )
        );
    }

    /**
     * @return int
     */
    private function getInterviewId(): int
    {
        return $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_INTERVIEW_ID
        );
    }

    /**
     * @param InterviewAttachment $interviewAttachment
     */
    private function setBase64Attachment(InterviewAttachment $interviewAttachment): void
    {
        $base64Attachment = $this->getRequestParams()->getAttachmentOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ATTACHMENT
        );
        if (is_null($base64Attachment)) {
            return;
        }
        $interviewAttachment->setFileName($base64Attachment->getFilename());
        $interviewAttachment->setFileType($base64Attachment->getFileType());
        $interviewAttachment->setFileSize($base64Attachment->getSize());
        $interviewAttachment->setFileContent($base64Attachment->getContent());
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_INTERVIEW_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Interview::class])
            ),
            new ParamRule(
                self::PARAMETER_ATTACHMENT,
                new Rule(
                    Rules::BASE_64_ATTACHMENT,
                    [
                        null,
                        null,
                        self::PARAM_RULE_FILE_NAME_MAX_LENGTH
                    ]
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_COMMENT_MAX_LENGTH]),
                ),
                true
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $interviewId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_INTERVIEW_ID
        );
        $toBeDeletedAttachmentIds = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_IDS
        );
        $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->deleteInterviewAttachments($interviewId, $toBeDeletedAttachmentIds);
        return new EndpointResourceResult(ArrayModel::class, $toBeDeletedAttachmentIds);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_INTERVIEW_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Interview::class])
            ),
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
        $attachmentId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_ATTACHMENT_ID
        );
        $interviewId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_INTERVIEW_ID
        );
        $interviewAttachment = $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->getPartialInterviewAttachmentByAttachmentIdAndInterviewId($attachmentId, $interviewId);
        $this->throwRecordNotFoundExceptionIfNotExist($interviewAttachment, RecruitmentAttachment::class);

        return new EndpointResourceResult(InterviewAttachmentModel::class, $interviewAttachment);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_INTERVIEW_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Interview::class])
            ),
            new ParamRule(
                self::PARAMETER_ATTACHMENT_ID,
                new Rule(Rules::POSITIVE)
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $attachmentId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_ATTACHMENT_ID
        );
        $currentAttachment = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_CURRENT_ATTACHMENT
        );
        $interviewId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_INTERVIEW_ID
        );
        $interviewAttachment = $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->getInterviewAttachmentByAttachmentIdAndInterviewId($attachmentId, $interviewId);

        $this->throwRecordNotFoundExceptionIfNotExist($interviewAttachment, InterviewAttachment::class);
        $this->setInterviewAttachment($interviewAttachment);
        if ($currentAttachment == self::INTERVIEW_ATTACHMENT_REPLACE_CURRENT) {
            $this->setBase64Attachment($interviewAttachment);
        }
        $this->getRecruitmentAttachmentService()
            ->getRecruitmentAttachmentDao()
            ->saveInterviewAttachment($interviewAttachment);
        return new EndpointResourceResult(
            InterviewAttachmentModel::class,
            $this->getRecruitmentAttachment($interviewAttachment)
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_ATTACHMENT_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_INTERVIEW_ID,
                new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Interview::class])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_COMMENT_MAX_LENGTH]),
                ),
                true
            ),
            new ParamRule(
                self::PARAMETER_CURRENT_ATTACHMENT,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_CURRENT_ATTACHMENT_MAX_LENGTH]),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_ATTACHMENT,
                    new Rule(
                        Rules::BASE_64_ATTACHMENT,
                        [null, null, self::PARAM_RULE_FILE_NAME_MAX_LENGTH]
                    )
                )
            )
        );
    }

    /**
     * @param InterviewAttachment $interviewAttachment
     * @return RecruitmentAttachment
     */
    private function getRecruitmentAttachment(InterviewAttachment $interviewAttachment): RecruitmentAttachment
    {
        return new RecruitmentAttachment(
            $interviewAttachment->getId(),
            $interviewAttachment->getFileName(),
            $interviewAttachment->getFileType(),
            $interviewAttachment->getFileSize(),
            $interviewAttachment->getInterview()->getId(),
            $interviewAttachment->getComment()
        );
    }
}
