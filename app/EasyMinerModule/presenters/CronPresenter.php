<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;

/**
 * Class CronPresenter - presenter for regular, periodical maintenance
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CronPresenter extends BasePresenter{
  use ResponsesTrait;

  const FILE_IMPORT_VALID_DAYS=2;
  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;

  /**
   * Action for delete of old import files
   * @inheritdoc run once per day
   */
  public function actionDeleteImportFiles(){
    $this->fileImportsFacade->deleteOldFiles(self::FILE_IMPORT_VALID_DAYS);
    $this->sendTextResponse('DONE');
  }

  #region injections
  /**
   * @param FileImportsFacade $fileImportsFacade
   */
  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }
  #endregion injections
}
