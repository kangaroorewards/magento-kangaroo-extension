<?php
/**
 * Handle kangaroorewards_credential
 */
namespace Kangaroorewards\Core\Model;
use Kangaroorewards\Core\Api\KangarooCredentialRepositoryInterface;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;

/**
 * Class KangarooCredentialRepository
 * @package Kangaroorewards\Core\Model
 */
class KangarooCredentialRepository 
    implements KangarooCredentialRepositoryInterface
{
    /**
     * @var KangarooCredentialFactory \
     */
    protected $credentialFactory;

    /**
     * @var Kangaroorewards\Core\Model\ResourceModel\KangarooCredential
     */
    protected $recourceModel;

    /**
     * KangarooCredentialRepository constructor.
     * @param KangarooCredentialFactory $credentialFactory
     * @param ResourceModel\KangarooCredential $resourceModel
     */
    public function __construct(
        \Kangaroorewards\Core\Model\KangarooCredentialFactory $credentialFactory,
        \Kangaroorewards\Core\Model\ResourceModel\KangarooCredential $resourceModel
    )
    {
        $this->credentialFactory = $credentialFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param \Kangaroorewards\Core\Api\Data\KangarooCredentialInterface $credential
     * @return \Kangaroorewards\Core\Api\Data\KangarooCredentialInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\Kangaroorewards\Core\Api\Data\KangarooCredentialInterface $credential)
    {
        $existingCredential = $this->credentialFactory->create()->load(1);
        $mergedData = array_merge($existingCredential->getData(), $credential->getData());
        $credential->setData($mergedData);
        $this->resourceModel->save($credential);
        return $credential;
    }
}