<?php
namespace Crealistiques\CreFalclearpagecache\Slots;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Crealistiques\CreFalclearpagecache\Utility\ClearPageCacheUtility;
use Crealistiques\CreFalclearpagecache\Utility\MetaDataUtility;

class FileIndexChangeSlot
{

    /**
     * Extension key
     *
     * @var string
     */
    protected $extKey = "cre_falclearpagecache";

    /**
     * Extension Configuration
     *
     * @var array
     */
    protected $extConf;

    /**
     * Object Manager
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor
     */
    public function __construct() {
        // Load extension configuration
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 9000000) {
            $this->extConf = (bool)\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
                ->get($this->extKey);
        } else {
            $this->extConf = $this->objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility')->getCurrentConfiguration($this->extKey);
        }
    }

    /**
     * Slot called the following signals emitted by FileIndexRepository:
     * recordUpdated            $fileData is file data array
     * recordCreated            $fileData is file data array
     * recordDeleted            $fileData is fileUid
     * recordMarkedAsMissing    $fileData is fileUid
     *
     * @param mixed $fileData
     * @return void
     */
    public function onFileIndexChange($fileData)
    {
        // Get storage uid if possible
        if (isset($fileData['storage'])) {
            $storage = intval($fileData['storage']);
        } else {
            $storage = -1;
        }

        // Check configuration if meta data extraction should be executed also
        if ($storage > 0 && isset($this->extConf['triggerMetaDataExtraction']) && $this->extConf['triggerMetaDataExtraction']['value']) {
            $metaDataUtility = GeneralUtility::makeInstance(MetaDataUtility::class);
            $metaDataUtility->runMetaDataExtraction($storage);
        }

        /**
         * @todo Include extconf pages/folders inclusion/exclusion
         */
        if ($fileData) {
            $clearPageCacheUtility = GeneralUtility::makeInstance(ClearPageCacheUtility::class);
            $clearPageCacheUtility->clearCacheOfPagesWithUploadsCEIncludingFile($fileData);
        }

    }



}
