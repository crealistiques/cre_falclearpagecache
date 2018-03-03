<?php
namespace Crealistiques\CreFalclearpagecache\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\FileStorageExtractionTask;

class MetaDataUtility
{

    /**
     * Run meta data extraction for given storage via FileStorageExtractionTask
     *
     * @param int $storage
     */
    public function runMetaDataExtraction($storage)
    {
        /** @var $fileStorageExtractionTask FileStorageExtractionTask */
        $fileStorageExtractionTask = GeneralUtility::makeInstance(FileStorageExtractionTask::class);
        $fileStorageExtractionTask->storageUid = $storage;
        try {
            $fileStorageExtractionTask->execute();
        } catch (\Exception $e) {
            GeneralUtility::sysLog($e->getMessage(), 'scheduler', GeneralUtility::SYSLOG_SEVERITY_ERROR);
        }
    }

}
