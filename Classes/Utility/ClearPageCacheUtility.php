<?php
namespace Crealistiques\CreFalclearpagecache\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Scheduler\Task\FileStorageExtractionTask;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;


class ClearPageCacheUtility
{

    /**
     * Object Manager
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Cache Service
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Constructor
     */
    public function __construct() {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->cacheService = $this->objectManager->get(CacheService::class);
    }

    /**
     * Clear cache of pages with an uploads content element including the file given by parameter fileUid.
     *
     * @param mixed $fileData
     * @return void
     */
    public function clearCacheOfPagesWithUploadsCEIncludingFile($fileData)
    {
        if (is_array($fileData)) {
            // File data array given, signal "recordUpdated" or "recordDeleted"
            $fileUid = $fileData['uid'];
            $storage = $fileData['storage'];
        } else if (is_integer($fileData)) {
            // File uid given, signal "recordDeleted" or "recordMarkedAsMissing"
            $fileUid = $fileData;
            $storage = -1;
        }

        // Get tt_content records of type "uploads" with reference to fileUid in field "media"
        $includedByReferenceRecordUids = $this->getRecordsWithReferencesOfFileUid($fileUid, "tt_content", "media");

        // Get tt_content records of type "uploads" with file collections set including fileUid
        $includedByFileCollectionRecordUids = $this->getRecordsOfTypeUploadsWithFileCollectionsIncludingFile($fileData);

        // Merge found tt_content record uids
        $ttContentRecordUids = array_merge($includedByReferenceRecordUids, $includedByFileCollectionRecordUids);

        // Clear cache for pages including given tt_content records
        $cacheClearedForPages = [];
        if (is_array($ttContentRecordUids) && count($ttContentRecordUids)) {
            foreach ($ttContentRecordUids as $ttContentUid) {
                $ttContentRecord = BackendUtility::getRecord('tt_content', $ttContentUid, '*', BackendUtility::BEenableFields('tt_content'));

                if (isset($ttContentRecord['pid'])
                    && $ttContentRecord['pid'] > 0
                    && !in_array($ttContentRecord['pid'], $cacheClearedForPages))
                {
                    // Clear cache for oage with uid = $ttContentRecord['pid']
                    $cacheClearedForPages[] = $ttContentRecord['pid'];
                    $this->cacheService->clearPageCache($ttContentRecord['pid']);
                }
            }
        }
    }

    /**
     * ABC
     *
     * @param array $fileData
     * @return array[int]
     */
    public function getRecordsOfTypeUploadsWithFileCollectionsIncludingFile($fileData)
    {

        $fileCollectionsIncludingFile = $this->getFileCollectionRecordsIncludingFile($fileData);

        $recordsReferencingFileCollections = [];
        foreach ($fileCollectionsIncludingFile as $fileCollectionUid) {
            $recordsReferencingFileCollections = array_merge(
                $recordsReferencingFileCollections,
                $this->getRecordsReferencingFileCollection($fileCollectionUid)
            );
        }

        return $recordsReferencingFileCollections;
    }

    /**
     * Get records of tt_content with CType "uploads" referencing fileCollectionUid
     *
     * @param int $fileCollectionUid
     * @return array[int]
     */
    public function getRecordsReferencingFileCollection($fileCollectionUid)
    {
        if (!MathUtility::canBeInterpretedAsInteger($fileCollectionUid)) {
            throw new \InvalidArgumentException(
                'UID of file collection has to be an integer. UID given: "' . $fileCollectionUid . '"',
                1311159798
            );
        }
        $recordsUids = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $res = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->Where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('uploads', \PDO::PARAM_STR)
                ),

                $queryBuilder->expr()->inSet(
                    'file_collections',
                    $queryBuilder->createNamedParameter($fileCollectionUid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        //$sql = $res->getSQL();

        while ($row = $res->fetch()) {
            // Collect tt_content record uids
            $recordsUids[] = $row['uid'];
        }

        return $recordsUids;
    }

    /**
     * Get records of sys_file_collection including file given by fileData.
     * Only file collection of type folder are supported at the moment!
     *
     * @TODO    Add support for category based file collections and static file collections
     *
     * @param array $fileData
     * @return array[integer]
     */
    public function getFileCollectionRecordsIncludingFile($fileData)
    {
        $fileCollectionUids = [];

        if (isset($fileData['uid']) && isset($fileData['identifier']) && isset($fileData['storage'])) {
            $fileUid = $fileData['uid'];
            $fileIdentifier = $fileData['identifier'];
            $fileFolder = pathinfo($fileIdentifier)['dirname'] . '/';
            $fileStorage = $fileData['storage'];

            // Build list of all parent folders of $fileFolder
            // to be used with IN expression in file collection query
            // E.g. $fileFolder = "/Folder/SubFolder/SubSubFolder/"
            $_folder = '';
            $fileFolderParts = explode('/', $fileFolder);
            $fileParentFolders = array_slice(
                array_map(
                    function($folderName) use (&$_folder) {
                        $_folder = $_folder . $folderName . '/';
                        return $_folder;
                    },
                    $fileFolderParts
                ),
                0,
                -1
            );
            // E.g. $fileParentFolders should look like this now:
            // $fileParentFolders =
            //  [
            //      "/Folder/",
            //      "/Folder/SubFolder/",
            //      "/Folder/SubFolder/SubSubFolder/"
            //  ]

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_collection');

            $res = $queryBuilder
                ->select('uid')
                ->from('sys_file_collection')
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'folder',
                        $queryBuilder->createNamedParameter($fileParentFolders, Connection::PARAM_STR_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        'recursive',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    )
                )
                ->orWhere(
                    $queryBuilder->expr()->eq(
                        'folder',
                        $queryBuilder->createNamedParameter($fileFolder, \PDO::PARAM_STR)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($fileStorage, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'type',
                        $queryBuilder->createNamedParameter('folder', \PDO::PARAM_STR)
                    )
                )
                // ->orderBy('sorting_foreign')
                ->execute();

            //$sql = $res->getSQL();

            while ($row = $res->fetch()) {
                // Collect uid of file collection record
                $fileCollectionUids[] = $row['uid'];
            }

        }
        return $fileCollectionUids;
    }

    /**
     * Find all records of given table with file references in given field to the file specified by given uid.
     *
     * @param int $fileUid Uid of file to find references for
     * @param string $tableName Table name of the related record
     * @param string $fieldName Field name of the related record
     */
    public function getRecordsWithReferencesOfFileUid($fileUid, $tableName = 'tt_content', $fieldName = 'media')
    {
        if (!MathUtility::canBeInterpretedAsInteger($fileUid)) {
            throw new \InvalidArgumentException(
                'UID of file has to be an integer. UID given: "' . $fileUid . '"',
                1316559798
            );
        }
        $recordsUids = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $res = $queryBuilder
            ->select('uid_foreign')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($fieldName, \PDO::PARAM_STR)
                )
            )
            ->orderBy('sorting_foreign')
            ->execute();

        while ($row = $res->fetch()) {
            // Collect uid of record of specified table
            $recordsUids[] = $row['uid_foreign'];
        }

        return $recordsUids;
    }

}
