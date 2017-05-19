<?php
namespace EasyMinerCenter\EasyMinerModule\Components;

/**
 * Interface IMetaAttributesSelectControlFactory
 * @package EasyMinerCenter\EasyMinerModule\Components
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IMetaAttributesSelectControlFactory {
  /** @return MetaAttributesSelectControl */
  function create();

} 