<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;
use EasyMinerCenter\EasyMinerModule\Components\IMetaAttributesSelectControlFactory;
use EasyMinerCenter\EasyMinerModule\Components\MetaAttributesSelectControl;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * Class CronPresenter - presenter pro pravidelnou údržbu
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class CronPresenter extends BasePresenter{

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
