<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;

/**
 * Class CronPresenter - presenter pro pravidelnou údržbu
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class CronPresenter extends BasePresenter{
  use ResponsesTrait;

  const FILE_IMPORT_VALID_DAYS=2;
  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;

  /**
   * Akce pro smazání starých souborů
   * @inheritdoc run once per day
   */
  public function actionDeleteImportFiles(){
    $this->fileImportsFacade->deleteOldFiles(self::FILE_IMPORT_VALID_DAYS);
    $this->sendTextResponse('DONE');
  }


  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }
}
