<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;

/**
 * Class PreprocessingServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Preprocessing
 *
 * @package EasyMinerCenter\Model\Data\Databases\DataService
 * @author Stanislav Vojíř
 */
abstract class PreprocessingServiceDatabase implements IPreprocessing {
  /** @var  string $apiKey */
  private $apiKey;
  /** @var  PpConnection $ppConnection */
  private $ppConnection;

  //TODO implement

}