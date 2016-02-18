<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 17. 2. 2016
 * Time: 17:34
 */

namespace EasyMinerCenter\app\model\data\databases;

use EasyMinerCenter\Model\Data\Databases\DataServiceDatabase;

/**
 * Class UnlimitedDatabase - přístup k UNLIMITED DB pomocí EasyMiner-Data
 * @package EasyMinerCenter\app\model\data\databases
 * @author Stanislav Vojíř
 */
class UnlimitedDatabase extends DataServiceDatabase{

  const DB_TYPE='unlimited';

}