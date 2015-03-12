<?php

namespace App\EasyMinerModule\Presenters;
use App\EasyMinerModule\Components\IMetaAttributesSelectControlFactory;
use App\EasyMinerModule\Components\MetaAttributesSelectControl;
use App\Libs\StringsHelper;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\Data\Facades\FileImportsFacade;
use App\Model\Data\Files\CsvImport;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Format;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\MetaAttributesFacade;
use App\Model\EasyMiner\Facades\UsersFacade;
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
 * @package App\EasyMinerModule\Presenters
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
