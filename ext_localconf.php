<?php

/**
 * Connect to signals emitted from FileIndexReository on FAL index changes
 */

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class,
    'recordUpdated',
    \Crealistiques\CreFalclearpagecache\Slots\FileIndexChangeSlot::class,
    'onFileIndexChange'
);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class,
    'recordCreated',
    \Crealistiques\CreFalclearpagecache\Slots\FileIndexChangeSlot::class,
    'onFileIndexChange'
);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class,
    'recordDeleted',
    \Crealistiques\CreFalclearpagecache\Slots\FileIndexChangeSlot::class,
    'onFileIndexChange'
);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class,
    'recordMarkedAsMissing',
    \Crealistiques\CreFalclearpagecache\Slots\FileIndexChangeSlot::class,
    'onFileIndexChange'
);


